<?php

namespace Database\Seeders;

use App\Models\Plans;
use App\Models\ProviderSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ModulePlansAndSubscriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedActiveSubscriptionForUser('nowsathnowsath93@gmail.com', 'user', 'classified', 'monthly');
        $this->seedActiveSubscriptionForUser('nowsathnowsath93@gmail.com', 'user', 'ecommerce', 'monthly');

        $vendor = User::query()->where('user_type', 'provider')->where('status', 1)->orderBy('id')->first();
        if ($vendor) {
            $this->seedActiveSubscription($vendor->id, 'ecommerce', 'yearly');
            $this->seedActiveSubscription($vendor->id, 'classified', 'yearly');
        }
    }

    private function seedActiveSubscriptionForUser(string $email, string $userType, string $module, string $planType): void
    {
        $user = User::query()->where('email', $email)->where('user_type', $userType)->where('status', 1)->first();
        if (! $user) {
            return;
        }
        $this->seedActiveSubscription($user->id, $module, $planType);
    }

    private function seedActiveSubscription(int $userId, string $module, string $planType): void
    {
        $plan = Plans::query()
            ->where('type', $planType)
            ->where('status', 1)
            ->where(function ($q) {
                if (Schema::hasColumn('plans', 'module')) {
                    $q->where('module', 'service');
                }
            })
            ->first();
        if (! $plan) {
            return;
        }

        $startAt = Carbon::now()->subDay();
        $endAt = match ($planType) {
            'weekly' => (clone $startAt)->addWeek(),
            'yearly' => (clone $startAt)->addYear(),
            default => (clone $startAt)->addMonth(),
        };

        ProviderSubscription::query()
            ->where('user_id', $userId)
            ->where('module', $module)
            ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
            ->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);

        ProviderSubscription::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'module' => $module,
                'status' => config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
            ],
            [
                'title' => $plan->title,
                'identifier' => $plan->identifier,
                'type' => $plan->type,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'amount' => $plan->amount,
                'duration' => $plan->duration,
                'description' => $plan->description,
                'plan_type' => $plan->plan_type ?: 'limited',
                'plan_limitation' => optional($plan->planlimit)->plan_limitation ? json_encode($plan->planlimit->plan_limitation) : null,
            ]
        );
    }
}
