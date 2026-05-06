<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\FrontendSettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserClassifiedPostController;
use App\Http\Controllers\UserProductCartController;
use App\Http\Controllers\UserProductOrderController;
use App\Http\Controllers\UserSubscriptionController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PostChatController;
use App\Http\Middleware\CheckInstallation;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__ . '/auth.php';


Route::middleware([CheckInstallation::class])->group(function () {
    Route::get('/', [FrontendController::class, 'index'])->name('frontend.index');
    Route::get('/login-page', [FrontendController::class, 'userLoginView'])->name('user.login');
    Route::post('/user-login', [CustomerController::class, 'userLogin'])->name('user.user_login');
    Route::get('/register-page', [FrontendController::class, 'userRegistrationView'])->name('user.register');
    Route::get('/provider-register', [FrontendController::class, 'partnerRegistrationView'])->name('partner.register');
    Route::get('/forgotpassword-page', [FrontendController::class, 'forgotPassword'])->name('user.forgot_password');

    Route::get('/category-list', [FrontendController::class, 'catgeoryList'])->name('category.list');
    Route::get('/subcategory-list', [FrontendController::class, 'subCatgeoryList'])->name('subcategory.list');
    Route::get('/service-list', [FrontendController::class, 'serviceList'])->name('service.list');
    Route::get('/product-list', [FrontendController::class, 'productList'])->name('product.list');
    Route::get('/post-list', [FrontendController::class, 'postList'])->name('post.list');
    Route::get('/blog-list', [FrontendController::class, 'blogList'])->name('blog.list');
    Route::get('/provider-list', [FrontendController::class, 'providerList'])->name('frontend.provider');
    Route::get('/shop-list', [FrontendController::class, 'shopList'])->name('shop.list');
    Route::get('/loyalty-points', [FrontendController::class, 'loyaltyPoints'])->name('loyalty.points');
    Route::get('/loyalty-history', [FrontendController::class, 'loyaltyHistoryList'])->name('loyalty.history');

    Route::get('/category-details/{id}', [FrontendController::class, 'categoryDetail'])->name('category.detail');
    Route::get('/blog-details/{id}', [FrontendController::class, 'blogDetail'])->name('blog.detail');
    Route::get('/provider-detail/{id}', [FrontendController::class, 'providerDetail'])->name('provider.detail');
    Route::get('/handyman-detail/{id}', [FrontendController::class, 'handymanDetail'])->name('handyman-detail');
    Route::get('/service-detail/{id}', [FrontendController::class, 'serviceDetail'])->name('service.detail');
    Route::get('/product-detail/{id}', [FrontendController::class, 'productDetail'])->name('product.detail');
    Route::get('/post-detail/{id}', [FrontendController::class, 'postDetail'])->name('post.detail');
    Route::get('/shop-detail/{id}', [FrontendController::class, 'shopDetail'])->name('shop.detail');

    
    Route::get('/privacy-policy', [FrontendController::class, 'privacyPolicy'])->name('user.privacy_policy');
    Route::get('/term-conditions', [FrontendController::class, 'termConditions'])->name('user.term_conditions');
    Route::get('/about-us', [FrontendController::class, 'aboutus'])->name('user.about_us');
    Route::get('/refund-policy', [FrontendController::class, 'refundPolicy'])->name('user.refund_policy');
    Route::get('/help-support', [FrontendController::class, 'helpSupport'])->name('user.help_support');
    Route::get('/data-deletion-request', [FrontendController::class, 'DataDeletion'])->name('user.data_deletion_request');

    Route::get('/favourite-service', [FrontendController::class, 'favouriteServiceList'])->name('favourite.service');
    Route::get('/service-packages', [FrontendController::class, 'servicePackageList'])->name('service.package');
    Route::get('/book-service', [FrontendController::class, 'bookServiceView'])->name('book.service');
    Route::get('/rating-all', [FrontendController::class, 'ratingList'])->name('rating.all');
    Route::get('/booking-detail/{id}', [FrontendController::class, 'bookingDetail'])->name('booking.detail');
});

Route::post('/cart/add-intent', [UserProductCartController::class, 'addIntentGuest'])->name('user.cart.add.intent');
Route::get('/my-posts/create-intent', [UserClassifiedPostController::class, 'createIntent'])->name('user.my-posts.create.intent');

Route::middleware(['auth'])->group(function () {
    Route::get('/my-cart', [UserProductCartController::class, 'index'])->name('user.cart');
    Route::post('/cart/add', [UserProductCartController::class, 'add'])->name('user.cart.add');
    Route::post('/cart/item/{cartItem}/quantity', [UserProductCartController::class, 'update'])->name('user.cart.update');
    Route::post('/cart/item/{cartItem}/remove', [UserProductCartController::class, 'remove'])->name('user.cart.remove');
    Route::post('/cart/checkout', [UserProductCartController::class, 'checkout'])->name('user.cart.checkout');
    Route::get('/save-product-stripe-payment/{id}', [UserProductCartController::class, 'saveStripePayment'])->name('user.product.stripe.save');
    Route::get('/product-razorpay-checkout/{id}', [UserProductCartController::class, 'razorpayCheckoutPage'])->name('user.product.razorpay.checkout');
    Route::post('/product-razorpay-verify/{id}', [UserProductCartController::class, 'verifyRazorpayPayment'])->name('user.product.razorpay.verify');
    Route::get('/product-gateway-checkout/{id}', [UserProductCartController::class, 'gatewayCheckoutPage'])->name('user.product.gateway.checkout');
    Route::post('/product-gateway-complete/{id}', [UserProductCartController::class, 'completeGatewayPayment'])->name('user.product.gateway.complete');
    Route::get('/my-product-orders', [UserProductOrderController::class, 'index'])->name('user.product-orders');
    Route::get('/my-product-orders/{productOrder}', [UserProductOrderController::class, 'show'])->name('user.product-order.show');
    Route::get('/my-subscriptions', [UserSubscriptionController::class, 'index'])->name('user.subscriptions.index');
    Route::post('/my-subscriptions', [UserSubscriptionController::class, 'store'])->name('user.subscriptions.store');
    Route::get('/save-subscription-stripe-payment/{id}', [UserSubscriptionController::class, 'saveStripePayment'])->name('user.subscription.stripe.save');
    Route::get('/subscription-razorpay-checkout/{id}', [UserSubscriptionController::class, 'razorpayCheckoutPage'])->name('user.subscription.razorpay.checkout');
    Route::post('/subscription-razorpay-verify/{id}', [UserSubscriptionController::class, 'verifyRazorpayPayment'])->name('user.subscription.razorpay.verify');
    Route::get('/subscription-gateway-checkout/{id}', [UserSubscriptionController::class, 'gatewayCheckoutPage'])->name('user.subscription.gateway.checkout');
    Route::post('/subscription-gateway-complete/{id}', [UserSubscriptionController::class, 'completeGatewayPayment'])->name('user.subscription.gateway.complete');
    Route::post('/my-subscriptions/{subscription}/cancel', [UserSubscriptionController::class, 'cancel'])->name('user.subscriptions.cancel');
    Route::get('/my-posts', [UserClassifiedPostController::class, 'index'])->name('user.my-posts');
    Route::get('/my-posts/create', [UserClassifiedPostController::class, 'create'])->name('user.my-posts.create');
    Route::post('/my-posts', [UserClassifiedPostController::class, 'store'])->name('user.my-posts.store');
    Route::get('/my-posts/{post}/edit', [UserClassifiedPostController::class, 'edit'])->name('user.my-posts.edit');
    Route::put('/my-posts/{post}', [UserClassifiedPostController::class, 'update'])->name('user.my-posts.update');
    Route::delete('/my-posts/{post}', [UserClassifiedPostController::class, 'destroy'])->name('user.my-posts.destroy');
    Route::post('/product/{product}/review', [ProductReviewController::class, 'store'])->name('product.review.store');
    Route::get('/post-chats', [PostChatController::class, 'index'])->name('post.chat.index');
    Route::post('/post/{post}/chat/start', [PostChatController::class, 'start'])->name('post.chat.start');
    Route::get('/post-chat/{conversation}', [PostChatController::class, 'show'])->name('post.chat.show');
    Route::post('/post-chat/{conversation}/send', [PostChatController::class, 'send'])->name('post.chat.send');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/helpdesk-list', [FrontendController::class, 'helpdeskList'])->name('helpdesk.list');
    Route::get('/helpdesk-datatable', [FrontendController::class, 'helpdeskDatatable'])->name('helpdesk.data');
    Route::get('/helpdesk-detail/{id}', [FrontendController::class, 'helpdeskDetail'])->name('helpdesk.detail');
    Route::get('/booking-list', [FrontendController::class, 'bookingList'])->name('booking.list');
    Route::get('/post-job-list', [FrontendController::class, 'postJobList'])->name('post.job.list');
    Route::post('save-favourite', [ServiceController::class, 'saveFavouriteService'])->name('save-favourite');
    Route::post('delete-favourite', [ServiceController::class, 'deleteFavouriteService'])->name('delete-favourite');
    Route::post('save-booking-rating', [BookingController::class, 'saveBookingRating'])->name('save-booking-rating');
    Route::post('save-recently-viewed/{serviceId}', [FrontendSettingController::class, 'recentlyViewedStore'])->name('save-recently-viewed');
    Route::get('get-recently-viewed', [FrontendSettingController::class, 'recentlyViewedGet'])->name('get-recently-viewed');
});
Route::post('/user/set-location', [FrontendController::class, 'setLocation'])->name('user.set-location');
Route::get('/category-datatable', [FrontendController::class, 'categoryDatatable'])->name('category.data');
Route::get('/subcategory-datatable', [FrontendController::class, 'subCategoryDatatable'])->name('subcategory.data');
Route::get('/service-datatable', [FrontendController::class, 'serviceDatatable'])->name('service.data');
Route::get('/product-datatable', [FrontendController::class, 'productDatatable'])->name('product.data');
Route::get('/post-datatable', [FrontendController::class, 'postDatatable'])->name('post.data');
Route::get('/blog-datatable', [FrontendController::class, 'blogDatatable'])->name('blog.data');
Route::get('/provider-datatable', [FrontendController::class, 'providerDatatable'])->name('provider.data');
Route::get('/shop-datatable', [FrontendController::class, 'shopDatatable'])->name('shop.data');
Route::get('/booking-datatable', [FrontendController::class, 'bookingDatatable'])->name('booking.data');
Route::get('/post-job-datatable', [FrontendController::class, 'postJobDatatable'])->name('post.job.data');
Route::get('/favouriteservice-datatable', [FrontendController::class, 'favouriteServiceDatatable'])->name('favouriteservice.data');
Route::get('/rating-datatable', [FrontendController::class, 'ratingDatatable'])->name('rating.data');
Route::post('/user-subscribe', [FrontendController::class, 'userSubscribe'])->name('user.subscribe');
