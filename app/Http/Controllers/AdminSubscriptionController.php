<?php

namespace App\Http\Controllers;

use App\Models\Plans;
use App\Models\ProviderSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminSubscriptionController extends Controller
{
    public function create(Request $request)
    {
        abort_unless(
            auth()->user()->hasAnyRole(['admin', 'demo_admin'])
            || auth()->user()->can('plan add')
            || auth()->user()->can('plan list'),
            403
        );

        $userId = (int) $request->get('user_id');
        $targetUser = User::query()
            ->where('id', $userId)
            ->whereIn('user_type', ['user', 'provider'])
            ->firstOrFail();

        $allowedModules = $targetUser->user_type === 'provider'
            ? ['service', 'ecommerce']
            : ['classified'];
        $defaultModule = $targetUser->user_type === 'provider' ? 'service' : 'classified';

        $module = $request->get('module', $defaultModule);
        if (! in_array($module, $allowedModules, true)) {
            $module = $defaultModule;
        }

        // One billing catalog (admin service plans); category only scopes the subscription record, not which plan rows load.
        $plans = Plans::query()
            ->where('status', 1)
            ->where('module', subscription_billing_plan_module())
            ->with('planlimit')
            ->orderBy('amount')
            ->get();

        $activeForModule = provider_subscriptions_valid_query($targetUser->id, $module)
            ->orderByDesc('id')
            ->first();

        $activePlanId = $activeForModule?->plan_id;

        $activeSubscriptions = provider_subscriptions_valid_query($targetUser->id, $module)
            ->orderByDesc('id')
            ->get();

        return view('subscription.extend', compact('targetUser', 'module', 'plans', 'activeSubscriptions', 'activeForModule', 'activePlanId'));
    }

    public function store(Request $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        abort_unless(
            auth()->user()->hasAnyRole(['admin', 'demo_admin'])
            || auth()->user()->can('plan add')
            || auth()->user()->can('plan list'),
            403
        );

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'module' => 'required|string|in:service,ecommerce,classified',
            'plan_id' => 'required|integer|exists:plans,id',
            'payment_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = User::query()
            ->where('id', (int) $validated['user_id'])
            ->whereIn('user_type', ['user', 'provider'])
            ->firstOrFail();

        $allowedModules = $user->user_type === 'provider'
            ? ['service', 'ecommerce']
            : ['classified'];
        if (! in_array($validated['module'], $allowedModules, true)) {
            throw ValidationException::withMessages([
                'module' => 'The selected category is not valid for this account type.',
            ]);
        }

        $plan = Plans::query()
            ->where('id', (int) $validated['plan_id'])
            ->where('status', 1)
            ->where('module', subscription_billing_plan_module())
            ->firstOrFail();

        $existing = get_user_active_plan($user->id, $validated['module']);

        // Period base for end_at:
        // - Fresh or lapsed (no valid row): from today.
        // - Active plan with end_at strictly in the future: from that expiry (extend after current period).
        $periodBaseForEnd = null;
        if ($existing) {
            $rawEnd = $existing->end_at ?? null;
            if ($rawEnd) {
                $oldEnd = Carbon::parse($rawEnd);
                if ($oldEnd->greaterThan(Carbon::now())) {
                    $periodBaseForEnd = $oldEnd->format('Y-m-d H:i:s');
                }
            }

            ProviderSubscription::query()
                ->where('id', (int) $existing->id)
                ->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);
        }

        $startAt = now()->format('Y-m-d H:i:s');
        if ($periodBaseForEnd === null) {
            $periodBaseForEnd = $startAt;
        }

        $endAt = $this->computeAdminExtendEndAt($periodBaseForEnd, $plan, 0);
        $endAt = subscription_end_at_or_fix($startAt, $endAt);

        ProviderSubscription::query()->create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'title' => $plan->title,
            'identifier' => $plan->identifier,
            'type' => $plan->type,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'amount' => $plan->amount,
            'status' => config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
            'payment_id' => null,
            'plan_limitation' => optional($plan->planlimit)->plan_limitation ? json_encode($plan->planlimit->plan_limitation) : null,
            'duration' => $plan->duration,
            'description' => trim(($plan->description ?? '').(!empty($validated['notes']) ? "\n\nAdmin note: ".$validated['notes'] : '')),
            'plan_type' => $plan->plan_type,
            'module' => $validated['module'],
        ]);

        $user->is_subscribe = provider_subscriptions_valid_query($user->id)->exists() ? 1 : 0;
        $user->save();

        return redirect()
            ->route('admin.subscription.extend', ['user_id' => $user->id, 'module' => $validated['module']])
            ->withSuccess(__('messages.update_form', ['form' => __('messages.plan')]));
    }

    /**
     * Add plan duration to a base datetime (admin extend / renew).
     * Mirrors plan types used by get_plan_expiration_date, with explicit weekly = duration weeks.
     */
    private function computeAdminExtendEndAt(string $periodBaseDate, Plans $plan, int $leftDays = 0): string
    {
        $type = strtolower(trim((string) $plan->type));
        $duration = (int) ($plan->duration ?? 1);
        if ($duration < 1) {
            $duration = 1;
        }
        $leftDays = max(0, $leftDays);
        $base = Carbon::parse($periodBaseDate);

        return match ($type) {
            'weekly' => $base->copy()->addWeeks($duration)->addDays($leftDays)->format('Y-m-d H:i:s'),
            'monthly' => $base->copy()->addMonths($duration)->addDays($leftDays)->format('Y-m-d H:i:s'),
            'yearly' => $base->copy()->addYears($duration)->addDays($leftDays)->format('Y-m-d H:i:s'),
            default => $base->copy()->addDays(7 + $leftDays)->format('Y-m-d H:i:s'),
        };
    }
}
