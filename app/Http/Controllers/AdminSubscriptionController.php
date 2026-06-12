<?php

namespace App\Http\Controllers;

use App\Models\Plans;
use App\Models\ProviderSubscription;
use App\Models\User;
use App\Models\UserPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $subscriptionModules = $targetUser->user_type === 'provider'
            ? ['service', 'ecommerce']
            : ['classified'];
        $module = $subscriptionModules[0];

        if ($targetUser->user_type === 'user') {
            $plans = UserPlan::query()
                ->where('status', 1)
                ->with('planlimit')
                ->orderBy('amount')
                ->get();
        } else {
            $plans = Plans::query()
                ->where('status', 1)
                ->where('module', subscription_billing_plan_module())
                ->with('planlimit')
                ->orderBy('amount')
                ->get();
        }

        $activeSubscriptions = provider_subscriptions_valid_query($targetUser->id)
            ->whereIn('module', $subscriptionModules)
            ->orderByDesc('id')
            ->get();

        $activePlanIds = $activeSubscriptions->pluck('plan_id')->filter()->unique();
        $activePlanId = $activeSubscriptions->count() === count($subscriptionModules) && $activePlanIds->count() === 1
            ? $activePlanIds->first()
            : null;

        return view('subscription.extend', compact('targetUser', 'module', 'plans', 'activeSubscriptions', 'activePlanId'));
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

        $user = User::query()
            ->where('id', (int) $request->get('user_id'))
            ->whereIn('user_type', ['user', 'provider'])
            ->firstOrFail();

        $planTable = $user->user_type === 'user' ? 'user_plan' : 'plans';

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'module' => 'required|string|in:service,ecommerce,classified',
            'plan_id' => 'required|integer|exists:' . $planTable . ',id',
            'payment_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $allowedModules = $user->user_type === 'provider'
            ? ['service', 'ecommerce']
            : ['classified'];
        if (! in_array($validated['module'], $allowedModules, true)) {
            throw ValidationException::withMessages([
                'module' => 'The selected category is not valid for this account type.',
            ]);
        }

        if ($user->user_type === 'user') {
            $plan = UserPlan::query()
                ->where('id', (int) $validated['plan_id'])
                ->where('status', 1)
                ->firstOrFail();
        } else {
            $plan = Plans::query()
                ->where('id', (int) $validated['plan_id'])
                ->where('status', 1)
                ->where('module', subscription_billing_plan_module())
                ->firstOrFail();
        }

        $subscriptionModules = $user->user_type === 'provider'
            ? ['service', 'ecommerce']
            : ['classified'];

        DB::transaction(function () use ($subscriptionModules, $user, $plan, $validated) {
            foreach ($subscriptionModules as $subscriptionModule) {
                $existing = get_user_active_plan($user->id, $subscriptionModule);

                // Extend each stored module from its own current expiry when it is still active.
                $periodBaseForEnd = null;
                if ($existing) {
                    $rawEnd = $existing->end_at ?? null;
                    if ($rawEnd) {
                        $oldEnd = Carbon::parse($rawEnd);
                        if ($oldEnd->greaterThan(Carbon::now())) {
                            $periodBaseForEnd = $oldEnd->format('Y-m-d H:i:s');
                        }
                    }
                }

                ProviderSubscription::query()
                    ->where('user_id', $user->id)
                    ->where('module', $subscriptionModule)
                    ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                    ->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);

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
                    'module' => $subscriptionModule,
                ]);
            }

            $user->is_subscribe = provider_subscriptions_valid_query($user->id)->exists() ? 1 : 0;
            $user->save();
        });

        return redirect()
            ->route('admin.subscription.extend', ['user_id' => $user->id])
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
