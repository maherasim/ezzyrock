<?php

namespace App\Providers;

use App\Models\ProductCartItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Route::bind('cartItem', function ($value) {
            if (! Schema::hasTable('product_cart_items')) {
                abort(404);
            }
            return ProductCartItem::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        View::composer('landing-page.partials._header', function ($view) {
            $cartCount = 0;
            if (auth()->check() && auth()->user()->user_type === 'user') {
                $cartCount = ProductCartItem::totalEcommerceQuantityForUser((int) auth()->id());
            }
            $view->with('headerCartCount', $cartCount);
        });
    }
}
