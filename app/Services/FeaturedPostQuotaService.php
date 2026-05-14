<?php

namespace App\Services;

use App\Models\FreePostSetting;
use App\Models\Post;
use App\Models\UserPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class FeaturedPostQuotaService
{
    public function getFreePostQuota(int $userId, ?int $excludePostId = null): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $freeLimit = $this->getFreeMonthlyLimit();

        $usedQuery = Post::query()
            ->where('provider_id', $userId)
            ->where('service_type', 'classified')
            ->where(function ($query) {
                $query->where('is_featured', 0)->orWhereNull('is_featured');
            })
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        if ($excludePostId) {
            $usedQuery->where('id', '!=', $excludePostId);
        }

        $usedThisMonth = (int) $usedQuery->count();
        $remaining = max(0, $freeLimit - $usedThisMonth);

        return [
            'monthly_limit' => $freeLimit,
            'used_this_month' => $usedThisMonth,
            'remaining' => $remaining,
            'allow_to_create_post' => $remaining > 0,
            'reset_at' => $monthEnd->toDateTimeString(),
        ];
    }

    public function getFeaturedQuota(int $userId, ?int $excludePostId = null): array
    {
        $subscription = user_subscriptions_valid_query($userId)
            ->latest('id')
            ->first();

        $paidLimit = 0;
        $isUnlimited = false;

        if ($subscription) {
            [$paidLimit, $isUnlimited] = $this->getPaidFeaturedLimit($subscription);
        }

        $usedQuery = Post::query()
            ->where('provider_id', $userId)
            ->where('service_type', 'classified')
            ->where('is_featured', 1);

        if ($subscription && ! empty($subscription->start_at) && ! empty($subscription->end_at)) {
            $usedQuery->whereBetween('created_at', [$subscription->start_at, $subscription->end_at]);
        }

        if ($excludePostId) {
            $usedQuery->where('id', '!=', $excludePostId);
        }

        $usedThisMonth = (int) $usedQuery->count();
        $totalLimit = $isUnlimited ? null : $paidLimit;
        $remaining = $isUnlimited ? null : max(0, $totalLimit - $usedThisMonth);

        return [
            'paid_plan_limit' => $isUnlimited ? null : $paidLimit,
            'total_limit' => $totalLimit,
            'used' => $usedThisMonth,
            'remaining' => $remaining,
            'is_unlimited' => $isUnlimited,
            'allow_to_create_featured' => $isUnlimited || $remaining > 0,
            'has_active_subscription' => (bool) $subscription,
            'subscription_id' => $subscription->id ?? null,
            'reset_at' => $subscription->end_at ?? null,
        ];
    }

    public function getQuota(int $userId, ?int $excludePostId = null): array
    {
        return $this->getFeaturedQuota($userId, $excludePostId);
    }

    private function getFreeMonthlyLimit(): int
    {
        if (! Schema::hasTable('free_post_settings')) {
            return 0;
        }

        return (int) FreePostSetting::query()
            ->where('status', 1)
            ->max('free_posts');
    }

    private function getPaidFeaturedLimit($subscription): array
    {
        $planKind = strtolower(trim((string) ($subscription->plan_type ?? '')));
        $planLimitation = json_decode($subscription->plan_limitation ?? '', true);

        if ((! is_array($planLimitation) || empty($planLimitation)) && ! empty($subscription->plan_id)) {
            $plan = UserPlan::query()->with('planlimit')->find($subscription->plan_id);
            if ($plan) {
                $planKind = strtolower(trim((string) ($plan->plan_type ?? $planKind)));
                $planLimitation = $plan->planlimit->plan_limitation ?? [];
            }
        }

        if ($planKind === 'unlimited') {
            return [0, true];
        }

        $featuredLimit = is_array($planLimitation) ? ($planLimitation['featured_classified'] ?? null) : null;
        if (! is_array($featuredLimit) || ($featuredLimit['is_checked'] ?? 'off') !== 'on') {
            return [0, false];
        }

        $limit = $featuredLimit['limit'] ?? null;
        if ($limit === null || $limit === '') {
            return [0, true];
        }

        return [max(0, (int) $limit), false];
    }
}
