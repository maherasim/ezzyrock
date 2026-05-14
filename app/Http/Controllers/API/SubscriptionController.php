<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UserPlan;
use App\Models\PaymentGateway;
use App\Models\ProviderSubscription;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use App\Models\User;
use App\Http\Resources\API\PlanResource;
use App\Http\Resources\API\PaymentGatewayResource;
use App\Http\Resources\API\ProviderSubscribeResource;
use App\Http\Requests\ProviderSubscriptionRequest;
use App\Traits\NotificationTrait;
use Razorpay\Api\Api as RazorpayApi;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    use NotificationTrait;
    public function providerSubscribe(ProviderSubscriptionRequest $request){

        $user_id = $request->user_id ? $request->user_id :auth()->id();

        $user = User::where('id',$user_id)->first();

        date_default_timezone_set(getTimeZone());

        $data = $request->all();

        // Billing plans live on `module = service`; subscription row still needs service|ecommerce|classified.
        $allowedModules = ['service', 'ecommerce', 'classified'];
        $module = $request->input('module');
        if (empty($module) || ! in_array($module, $allowedModules, true)) {
            $module = 'service';
        }

        $get_existing_plan = get_user_active_plan($user_id, $module);

        $active_plan_left_days = 0;

        $data['user_id'] = $user_id;
        $data['module'] = $module;

        $data['status'] = config('constant.SUBSCRIPTION_STATUS.PENDING');


        $data['start_at'] = date('Y-m-d H:i:s');

        if($get_existing_plan){
            $active_plan_left_days  = check_days_left_plan($get_existing_plan,$data);
            if($request->identifier  != $get_existing_plan->identifier){
                $get_existing_plan->update([
                    'status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')
                ]);
                $get_existing_plan->save();
            }
        }

        $data['end_at'] = get_plan_expiration_date($data['start_at'], $data['type'], $active_plan_left_days, $data['duration']);
        $data['end_at'] = subscription_end_at_or_fix($data['start_at'], $data['end_at']);
        if(isset($data['plan_limitation']) && !empty($data['plan_limitation'] )){
            $data['plan_limitation'] = json_encode($data['plan_limitation']);
        }
        $result = ProviderSubscription::create($data);

        if( $result ){
            $payment_data =[
                'subscription_plan_id' => $result->id,
                'user_id' => $result->user_id,
                'amount' => $result->amount,
                'payment_status' => $request->payment_status,
                'payment_type' => $request->payment_type
            ];
           $payment = SubscriptionTransaction::create($payment_data);

           if($payment->payment_status == 'paid'){
                $result->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
                $result->payment_id = $payment->id;
                $result->save();
                $user->is_subscribe = 1;
                $user->save();
                $message = __('messages.payment_completed');
           }
        }

        $items = new ProviderSubscribeResource($result);

        $response = [
            'data' => $items,
        ];

        $activity_data = [
            'activity_type' => 'subscription_add',
            'subscription_data' => $result,
        ];
        $this->sendNotification($activity_data);

        return comman_custom_response($response);
    }

    public function cancelSubscription(Request $request){
        $user_id = $request->user_id ? $request->user_id : auth()->id();
        $plan_id  = $request->id;
        $provider_subscription = ProviderSubscription::where('id', $plan_id )->where('user_id',$user_id)->first();
        $user = User::where('id', $user_id)->first();
        if($provider_subscription){
            $provider_subscription->status =  config('constant.SUBSCRIPTION_STATUS.CANCELED');
            $provider_subscription->save();
            $user->is_subscribe = 0;
            $user->save();
            $message = __('messages.cancelled_plan',['plan'=> $provider_subscription->title]);
        }
        return comman_message_response($message);
    }

    public function getHistory(Request $request){
        $user_id = auth()->id();
        $subscription_history = ProviderSubscription::where('user_id',$user_id);
        $per_page = config('constant.PER_PAGE_LIMIT');

        $orderBy = $request->orderby ? $request->orderby: 'asc';

        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $subscription_history->count();
            }
        }

        $subscription_history = $subscription_history->orderBy('id',$orderBy)->paginate($per_page);
        $items = ProviderSubscribeResource::collection($subscription_history);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);

    }

    public function subscriptionConfig(Request $request)
    {
        $plansQuery = UserPlan::query()->where('status', 1);
        $plans = $plansQuery->orderBy('amount')->get();
        $gateways = PaymentGateway::query()
            ->where('status', 1)
            ->whereIn('type', ['stripe', 'razorPay', 'phonepe', 'paypal', 'paystack', 'flutterwave'])
            ->get();

        return comman_custom_response([
            'plans' => PlanResource::collection($plans),
            'payment_methods' => PaymentGatewayResource::collection($gateways),
        ]);
    }

    public function subscriptionCheckout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:user_plan,id',
            'payment_method' => 'required|in:stripe,razorPay,razorpay,phonepe,paypal,paystack,flutterwave',
        ]);

        $module = 'classified'; // User subscriptions use classified module
        $plan = UserPlan::query()
            ->where('status', 1)
            ->with('planlimit')
            ->findOrFail((int) $request->plan_id);

        $paymentType = $this->normalizePaymentMethod((string) $request->payment_method);
        $userId = auth()->id();
        $startAt = now()->format('Y-m-d H:i:s');
        $activeCurrent = user_subscriptions_valid_query($userId, $module)->latest('id')->first();
        $activePlanLeftDays = $activeCurrent ? check_days_left_plan($activeCurrent, ['start_at' => $startAt]) : 0;
        $endAt = get_plan_expiration_date($startAt, (string) $plan->type, (int) $activePlanLeftDays, (int) ($plan->duration ?: 1));
        $endAt = subscription_end_at_or_fix($startAt, $endAt);
        $planLimitation = $plan->planlimit->plan_limitation ?? null;

        $subscription = UserSubscription::query()->create([
            'plan_id' => $plan->id,
            'user_id' => $userId,
            'title' => $plan->title,
            'identifier' => $plan->identifier,
            'type' => $plan->type,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'amount' => (float) $plan->amount,
            'status' => config('constant.SUBSCRIPTION_STATUS.PENDING'),
            'plan_limitation' => is_array($planLimitation) ? json_encode($planLimitation) : $planLimitation,
            'duration' => (int) ($plan->duration ?: 1),
            'description' => $plan->description,
            'plan_type' => $plan->plan_type,
            'module' => $module,
        ]);

        $payment = SubscriptionTransaction::query()->create([
            'subscription_plan_id' => null,
            'user_id' => $userId,
            'amount' => (float) $plan->amount,
            'payment_type' => $paymentType,
            'payment_status' => 'pending',
        ]);

        $subscription->payment_id = $payment->id;
        $subscription->save();

        if ((float) $plan->amount <= 0) {
            $this->markUserSubscriptionPaid($subscription, $payment, 'free', 'free-' . $subscription->id);
            return comman_custom_response([
                'status' => true,
                'message' => __('messages.subscription_added'),
                'subscription' => new ProviderSubscribeResource($subscription),
            ]);
        }

        if ($paymentType === 'razorPay') {
            $razorpay = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
            $cfg = $this->getGatewayConfig($razorpay);
            if (empty($cfg['razor_key']) || empty($cfg['razor_secret'])) {
                return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
            }
            $api = new RazorpayApi($cfg['razor_key'], $cfg['razor_secret']);
            $rzOrder = $api->order->create([
                'receipt' => 'SUB-' . $subscription->id,
                'amount' => (int) round(((float) $subscription->amount) * 100),
                'currency' => 'INR',
            ]);
            $payment->other_transaction_detail = json_encode([
                'razorpay_order_id' => (string) $rzOrder['id'],
                'razorpay_is_test' => (int) ($razorpay->is_test ?? 1),
                'razorpay_key' => (string) $cfg['razor_key'],
            ]);
            $payment->save();

            return comman_custom_response([
                'status' => true,
                'subscription' => [
                    'id' => $subscription->id,
                ],
                'payment_action' => [
                    'type' => 'razorpay',
                    'razorpay_key' => (string) $cfg['razor_key'],
                    'razorpay_order_id' => (string) $rzOrder['id'],
                    'amount' => (int) round(((float) $subscription->amount) * 100),
                    'currency' => 'INR',
                    'verify_endpoint' => url('/api/user-subscription-razorpay-verify'),
                ],
            ]);
        }

        if ($paymentType === 'phonepe') {
            $phonepe = PaymentGateway::query()->where('type', 'phonepe')->where('status', 1)->first();
            $cfg = $this->getGatewayConfig($phonepe);
            if (empty($cfg['merchant_id']) || empty($cfg['salt_key'])) {
                return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
            }
            $apiPath = '/pg/v1/pay';
            $merchantTransactionId = 'SUBPP' . time() . $subscription->id;
            $payload = [
                'merchantId' => $cfg['merchant_id'],
                'merchantTransactionId' => $merchantTransactionId,
                'amount' => (int) round(((float) $subscription->amount) * 100),
                'redirectUrl' => url('/api/subscription-phonepe/callback?subscription_id=' . $subscription->id),
                'redirectMode' => 'POST',
                'callbackUrl' => url('/api/subscription-phonepe/callback?subscription_id=' . $subscription->id),
                'paymentInstrument' => ['type' => 'PAY_PAGE'],
            ];
            $encoded = base64_encode(json_encode($payload));
            $checksum = $this->generatePhonePeChecksum($encoded, $apiPath, (string) $cfg['salt_key'], (string) ($cfg['salt_index'] ?? 1));
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => (string) $cfg['merchant_id'],
            ])->post($this->getPhonePeBaseUrl((int) ($phonepe->is_test ?? 1)) . $apiPath, ['request' => $encoded]);
            $json = $response->json();
            $payUrl = data_get($json, 'data.instrumentResponse.redirectInfo.url');
            if (! $response->successful() || empty($payUrl)) {
                Log::error('Subscription PhonePe initiate failed', ['response' => $json]);
                return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
            }
            $payment->other_transaction_detail = json_encode(['merchant_transaction_id' => $merchantTransactionId]);
            $payment->save();

            return comman_custom_response([
                'status' => true,
                'checkout_url' => $payUrl,
                'payment_type' => $paymentType,
                'subscription' => new ProviderSubscribeResource($subscription),
            ]);
        }

        if (in_array($paymentType, ['paypal', 'paystack', 'flutterwave'], true)) {
            return comman_custom_response([
                'status' => true,
                'checkout_url' => route('user.subscription.gateway.checkout', $subscription->id),
                'payment_type' => $paymentType,
                'subscription' => new ProviderSubscribeResource($subscription),
            ]);
        }

        $stripeData = getPaymentMethodkey('stripe');
        $stripeSecret = is_array($stripeData) ? ($stripeData['stripe_key'] ?? null) : null;
        if (empty($stripeSecret)) {
            return response()->json(['status' => false, 'message' => __('messages.stripe_details')], 422);
        }

        $stripe = new StripeClient($stripeSecret);
        $checkoutSession = $stripe->checkout->sessions->create([
            'success_url' => url('/save-subscription-stripe-payment/' . $subscription->id),
            'cancel_url' => route('user.subscriptions.index'),
            'payment_method_types' => ['card'],
            'billing_address_collection' => 'required',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'inr',
                    'product_data' => ['name' => 'Subscription ' . $subscription->title],
                    'unit_amount' => (int) round(((float) $subscription->amount) * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
        ]);

        $payment->other_transaction_detail = (string) $checkoutSession->id;
        $payment->save();

        return comman_custom_response([
            'status' => true,
            'checkout_url' => $checkoutSession->url,
            'payment_type' => $paymentType,
            'session_id' => $checkoutSession->id,
            'subscription' => new ProviderSubscribeResource($subscription),
        ]);
    }

    public function verifyUserSubscriptionRazorpay(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|integer',
            'order_id' => 'nullable|integer',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $subscription = UserSubscription::query()
            ->where('user_id', auth()->id())
            ->find($validated['subscription_id']);

        if (! $subscription) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $payment = SubscriptionTransaction::query()->find($subscription->payment_id);
        if (! $payment) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $meta = json_decode((string) ($payment->other_transaction_detail ?? '{}'), true);
        if (($meta['razorpay_order_id'] ?? '') !== (string) $validated['razorpay_order_id']) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }

        $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
        $cfg = $this->getGatewayConfig($gateway, $meta['razorpay_is_test'] ?? null);
        $razorSecret = $cfg['razor_secret'] ?? null;
        if (empty($razorSecret)) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }

        $signatureData = $validated['razorpay_order_id'] . '|' . $validated['razorpay_payment_id'];
        $expectedSignature = hash_hmac('sha256', $signatureData, (string) $razorSecret);
        if (! hash_equals($expectedSignature, (string) $validated['razorpay_signature'])) {
            return response()->json(['status' => false, 'message' => __('messages.payment_message', ['status' => __('messages.failed')])], 422);
        }

        $this->markUserSubscriptionPaid($subscription, $payment, 'razorPay', (string) $validated['razorpay_payment_id']);

        return response()->json([
            'status' => true,
            'message' => __('messages.subscription_added'),
            'subscription' => [
                'id' => $subscription->id,
            ],
        ]);
    }

    public function userSubscriptionHistory(Request $request)
    {
        $userId = auth()->id();
        $query = UserSubscription::query()
            ->where('user_id', $userId)
            ->with('payment');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        $perPage = config('constant.PER_PAGE_LIMIT', 15);
        if ($request->filled('per_page')) {
            $perPage = $request->per_page === 'all'
                ? max(1, (clone $query)->count())
                : (int) $request->per_page;
        }

        $orderBy = strtolower((string) $request->get('orderby', 'desc')) === 'asc' ? 'asc' : 'desc';
        $subscriptions = $query->orderBy('id', $orderBy)->paginate($perPage);

        return comman_custom_response([
            'pagination' => [
                'total_items' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage(),
                'currentPage' => $subscriptions->currentPage(),
                'totalPages' => $subscriptions->lastPage(),
                'from' => $subscriptions->firstItem(),
                'to' => $subscriptions->lastItem(),
                'next_page' => $subscriptions->nextPageUrl(),
                'previous_page' => $subscriptions->previousPageUrl(),
            ],
            'data' => $subscriptions->getCollection()
                ->map(function (UserSubscription $subscription) {
                    return $this->formatUserSubscriptionHistoryItem($subscription);
                })
                ->values(),
        ]);
    }

    private function formatUserSubscriptionHistoryItem(UserSubscription $subscription): array
    {
        $payment = $subscription->payment;
        $status = (string) $subscription->status;
        $isActive = $status === config('constant.SUBSCRIPTION_STATUS.ACTIVE')
            && (! $subscription->end_at || now()->lessThanOrEqualTo($subscription->end_at));
        $isExpired = ! empty($subscription->end_at) && now()->greaterThan($subscription->end_at);
        $planLimitation = json_decode((string) $subscription->plan_limitation, true);
        $featuredLimit = $planLimitation['featured_classified']['limit'] ?? null;

        return [
            'id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'title' => $subscription->title,
            'identifier' => $subscription->identifier,
            'type' => $subscription->type,
            'amount' => (float) $subscription->amount,
            'status' => $status,
            'computed_status' => $isExpired && $status === config('constant.SUBSCRIPTION_STATUS.ACTIVE') ? 'expired' : $status,
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'start_at' => $subscription->start_at,
            'end_at' => $subscription->end_at,
            'days_left' => $isActive && $subscription->end_at ? now()->diffInDays($subscription->end_at, false) : 0,
            'duration' => $subscription->duration,
            'plan_type' => $subscription->plan_type,
            'module' => $subscription->module,
            'featured_posts_limit' => $featuredLimit === null || $featuredLimit === '' ? null : (int) $featuredLimit,
            'plan_limitation' => $planLimitation ?: null,
            'payment' => $payment ? [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_type' => $payment->payment_type,
                'payment_status' => $payment->payment_status,
                'txn_id' => $payment->txn_id,
                'created_at' => $payment->created_at,
            ] : null,
            'created_at' => $subscription->created_at,
            'updated_at' => $subscription->updated_at,
        ];
    }

    private function markSubscriptionPaid(ProviderSubscription $subscription, SubscriptionTransaction $payment, string $paymentType, string $txnId): void
    {
        DB::transaction(function () use ($subscription, $payment, $paymentType, $txnId) {
            ProviderSubscription::query()
                ->where('user_id', $subscription->user_id)
                ->where('module', $subscription->module)
                ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                ->where('id', '!=', $subscription->id)
                ->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);

            $payment->payment_type = $paymentType;
            $payment->payment_status = 'paid';
            $payment->txn_id = $txnId;
            $payment->save();

            $subscription->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
            $subscription->payment_id = $payment->id;
            $subscription->save();

            User::query()->where('id', $subscription->user_id)->update(['is_subscribe' => 1]);
        });
    }

    private function markUserSubscriptionPaid(UserSubscription $subscription, SubscriptionTransaction $payment, string $paymentType, string $txnId): void
    {
        DB::transaction(function () use ($subscription, $payment, $paymentType, $txnId) {
            UserSubscription::query()
                ->where('user_id', $subscription->user_id)
                ->where('module', $subscription->module)
                ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                ->where('id', '!=', $subscription->id)
                ->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);

            $payment->payment_type = $paymentType;
            $payment->payment_status = 'paid';
            $payment->txn_id = $txnId;
            $payment->save();

            $subscription->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
            $subscription->payment_id = $payment->id;
            $subscription->save();

            User::query()->where('id', $subscription->user_id)->update(['is_subscribe' => 1]);
        });
    }

    private function getGatewayConfig(?PaymentGateway $gateway, ?int $isTest = null): array
    {
        if (! $gateway) {
            return [];
        }
        $useTest = $isTest ?? (int) $gateway->is_test;
        $payload = $useTest === 1 ? $gateway->value : $gateway->live_value;
        return json_decode((string) $payload, true) ?? [];
    }

    private function normalizePaymentMethod(string $paymentMethod): string
    {
        return $paymentMethod === 'razorpay' ? 'razorPay' : $paymentMethod;
    }

    private function generatePhonePeChecksum(string $payload, string $apiPath, string $saltKey, string $saltIndex = '1'): string
    {
        $hash = hash('sha256', $payload . $apiPath . $saltKey);
        return $hash . '###' . $saltIndex;
    }

    private function getPhonePeBaseUrl(int $isTest): string
    {
        return $isTest === 0
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    // public function providerSubscriptionDetail($id){
    //     // $user_id = auth()->id();
    //     $subscription = ProviderSubscription::where('user_id', $id)->get();

    //     return($subscription);
    // }
}
