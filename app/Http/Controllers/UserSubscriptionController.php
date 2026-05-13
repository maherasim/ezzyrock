<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\UserPlan;
use App\Models\UserSubscription;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api as RazorpayApi;
use Stripe\StripeClient;

class UserSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->ensureCustomer();
        $module = 'classified';

        // Customer subscriptions use the user plan catalog; subscription `module` stays classified for limits.
        $plans = UserPlan::query()
            ->where('status', 1)
            ->with('planlimit')
            ->orderBy('amount')
            ->get();

        // Same rules as plan limits: active status + not expired (end_at).
        $activeSubscription = user_subscriptions_valid_query((int) auth()->id(), $module)
            ->latest('id')
            ->first();

        $history = UserSubscription::query()
            ->where('user_id', auth()->id())
            ->where('module', $module)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('landing-page.user-subscriptions', compact('module', 'plans', 'activeSubscription', 'history'));
    }

    public function store(Request $request)
    {
        $this->ensureCustomer();
        $request->validate([
            'plan_id' => 'required|integer|exists:user_plan,id',
            'payment_method' => 'required|in:stripe,razorPay,phonepe,paypal,paystack,flutterwave',
        ]);

        $module = 'classified';
        $plan = UserPlan::query()
            ->where('status', 1)
            ->with('planlimit')
            ->findOrFail((int) $request->plan_id);
        $paymentType = (string) $request->payment_method;
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

        // Free plans: activate immediately (no payment gateway step).
        if ((float) $plan->amount <= 0) {
            $this->markSubscriptionPaid($subscription, $payment, 'free', 'free-' . $subscription->id);

            return redirect()->route('user.subscriptions.index')
                ->with('success', __('messages.subscription_added'));
        }

        if ($paymentType === 'razorPay') {
            $razorpay = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
            $cfg = $this->getGatewayConfig($razorpay);
            if (empty($cfg['razor_key']) || empty($cfg['razor_secret'])) {
                return redirect()->route('user.subscriptions.index')->withErrors(__('messages.something_wrong'));
            }
            $api = new RazorpayApi($cfg['razor_key'], $cfg['razor_secret']);
            $rzOrder = $api->order->create([
                'receipt' => 'SUB-' . $subscription->id,
                'amount' => (int) round(((float) $subscription->amount) * 100),
                'currency' => 'INR',
            ]);
            $payment->other_transaction_detail = json_encode(['razorpay_order_id' => (string) $rzOrder['id']]);
            $payment->save();

            return redirect()->route('user.subscription.razorpay.checkout', $subscription->id);
        }

        if ($paymentType === 'phonepe') {
            $phonepe = PaymentGateway::query()->where('type', 'phonepe')->where('status', 1)->first();
            $cfg = $this->getGatewayConfig($phonepe);
            if (empty($cfg['merchant_id']) || empty($cfg['salt_key'])) {
                return redirect()->route('user.subscriptions.index')->withErrors(__('messages.something_wrong'));
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
                return redirect()->route('user.subscriptions.index')->withErrors(__('messages.something_wrong'));
            }
            $payment->other_transaction_detail = json_encode(['merchant_transaction_id' => $merchantTransactionId]);
            $payment->save();

            return redirect()->away((string) $payUrl);
        }

        if (in_array($paymentType, ['paypal', 'paystack', 'flutterwave'], true)) {
            return redirect()->route('user.subscription.gateway.checkout', $subscription->id);
        }

        $stripeData = getPaymentMethodkey('stripe');
        $stripeSecret = is_array($stripeData) ? ($stripeData['stripe_key'] ?? null) : null;
        if (empty($stripeSecret)) {
            return redirect()->route('user.subscriptions.index')->withErrors(__('messages.stripe_details'));
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

        return redirect($checkoutSession->url);
    }

    public function saveStripePayment(int $id)
    {
        $subscription = UserSubscription::query()->where('user_id', auth()->id())->findOrFail($id);
        $payment = SubscriptionTransaction::query()->find($subscription->payment_id);
        if (! $payment || empty($payment->other_transaction_detail)) {
            return redirect()->route('user.subscriptions.index')->withErrors(__('messages.something_wrong'));
        }
        $sessionObject = getstripePaymnetId((string) $payment->other_transaction_detail, 'stripe');
        if (!empty($sessionObject['payment_intent']) && ($sessionObject['payment_status'] ?? '') === 'paid') {
            $this->markSubscriptionPaid($subscription, $payment, 'stripe', (string) $sessionObject['payment_intent']);
            return redirect()->route('user.subscriptions.index')->with('success', __('messages.subscription_added'));
        }

        return redirect()->route('user.subscriptions.index')->withErrors(__('messages.payment_message', ['status' => __('messages.failed')]));
    }

    public function razorpayCheckoutPage(int $id)
    {
        $subscription = UserSubscription::query()->where('user_id', auth()->id())->findOrFail($id);
        $payment = SubscriptionTransaction::query()->findOrFail($subscription->payment_id);
        $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
        $cfg = $this->getGatewayConfig($gateway);
        $meta = json_decode((string) ($payment->other_transaction_detail ?? '{}'), true);
        $razorOrderId = $meta['razorpay_order_id'] ?? null;
        if (empty($cfg['razor_key']) || empty($razorOrderId)) {
            return redirect()->route('user.subscriptions.index')->withErrors(__('messages.something_wrong'));
        }

        $razorKey = $cfg['razor_key'];
        return view('landing-page.user-subscription-razorpay-checkout', compact('subscription', 'razorKey', 'razorOrderId'));
    }

    public function verifyRazorpayPayment(Request $request, int $id): JsonResponse
    {
        $subscription = UserSubscription::query()->where('user_id', auth()->id())->findOrFail($id);
        $payment = SubscriptionTransaction::query()->findOrFail($subscription->payment_id);
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);
        $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
        $cfg = $this->getGatewayConfig($gateway);
        $meta = json_decode((string) ($payment->other_transaction_detail ?? '{}'), true);
        if (($meta['razorpay_order_id'] ?? '') !== (string) $request->razorpay_order_id) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }
        $expected = hash_hmac('sha256', $request->razorpay_order_id . '|' . $request->razorpay_payment_id, (string) ($cfg['razor_secret'] ?? ''));
        if (! hash_equals($expected, (string) $request->razorpay_signature)) {
            return response()->json(['status' => false, 'message' => __('messages.payment_message', ['status' => __('messages.failed')])], 422);
        }
        $this->markSubscriptionPaid($subscription, $payment, 'razorPay', (string) $request->razorpay_payment_id);

        return response()->json(['status' => true, 'redirect' => route('user.subscriptions.index')]);
    }

    public function gatewayCheckoutPage(int $id)
    {
        $subscription = UserSubscription::query()->where('user_id', auth()->id())->findOrFail($id);
        $payment = SubscriptionTransaction::query()->findOrFail($subscription->payment_id);
        $paymentType = (string) ($payment->payment_type ?? '');
        abort_unless(in_array($paymentType, ['paypal', 'paystack', 'flutterwave'], true), 404);
        $gateway = PaymentGateway::query()->where('type', $paymentType)->where('status', 1)->first();
        $gatewayConfig = $this->getGatewayConfig($gateway);

        return view('landing-page.user-subscription-gateway-checkout', compact('subscription', 'paymentType', 'gatewayConfig'));
    }

    public function completeGatewayPayment(Request $request, int $id): JsonResponse
    {
        $subscription = UserSubscription::query()->where('user_id', auth()->id())->findOrFail($id);
        $payment = SubscriptionTransaction::query()->findOrFail($subscription->payment_id);
        $request->validate([
            'gateway' => 'required|in:paypal,paystack,flutterwave',
            'transaction_id' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|string',
        ]);
        $status = strtolower((string) $request->status);
        if (! in_array($status, ['success', 'succeeded', 'completed', 'paid'], true)) {
            $payment->payment_status = 'failed';
            $payment->save();
            return response()->json(['status' => false, 'redirect' => route('user.subscriptions.index')], 422);
        }
        $txnId = (string) ($request->transaction_id ?: $request->reference ?: ('TXN-' . time()));
        $this->markSubscriptionPaid($subscription, $payment, (string) $request->gateway, $txnId);

        return response()->json(['status' => true, 'redirect' => route('user.subscriptions.index')]);
    }

    public function subscriptionPhonePeCallback(Request $request)
    {
        $subscriptionId = (int) ($request->query('subscription_id') ?? data_get($request->all(), 'data.subscription_id'));
        $subscription = UserSubscription::query()->find($subscriptionId);
        if (! $subscription) {
            return response()->json(['status' => false], 404);
        }
        $payment = SubscriptionTransaction::query()->find($subscription->payment_id);
        if (! $payment) {
            return response()->json(['status' => false], 404);
        }
        if (data_get($request->all(), 'code') === 'PAYMENT_SUCCESS') {
            $txnId = (string) (data_get($request->all(), 'data.transactionId') ?? '');
            $this->markSubscriptionPaid($subscription, $payment, 'phonepe', $txnId);
            return redirect()->route('user.subscriptions.index')->with('success', __('messages.subscription_added'));
        }
        $payment->payment_status = 'failed';
        $payment->save();
        return redirect()->route('user.subscriptions.index')->withErrors(__('messages.payment_message', ['status' => __('messages.failed')]));
    }

    public function cancel(Request $request, UserSubscription $subscription)
    {
        $this->ensureCustomer();
        abort_unless($subscription->user_id === auth()->id(), 403);

        $subscription->status = config('constant.SUBSCRIPTION_STATUS.CANCELED');
        $subscription->save();

        $hasAnyActive = UserSubscription::query()
            ->where('user_id', auth()->id())
            ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
            ->exists();

        auth()->user()->update([
            'is_subscribe' => $hasAnyActive ? 1 : 0,
        ]);

        return redirect()
            ->route('user.subscriptions.index')
            ->with('success', __('messages.cancelled_plan', ['plan' => $subscription->title]));
    }

    private function ensureCustomer(): void
    {
        abort_unless(auth()->user()->user_type === 'user', 403);
    }

    private function markSubscriptionPaid(UserSubscription $subscription, SubscriptionTransaction $payment, string $paymentType, string $txnId): void
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

    private function getGatewayConfig(?PaymentGateway $gateway): array
    {
        if (! $gateway) {
            return [];
        }
        $payload = $gateway->is_test == 1 ? $gateway->value : $gateway->live_value;
        return json_decode((string) $payload, true) ?? [];
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

}
