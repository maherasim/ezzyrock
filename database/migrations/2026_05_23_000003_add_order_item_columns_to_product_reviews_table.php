<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::table('product_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('product_reviews', 'product_order_id')) {
                $table->unsignedBigInteger('product_order_id')->nullable()->after('product_id')->index();
            }
            if (! Schema::hasColumn('product_reviews', 'product_order_item_id')) {
                $table->unsignedBigInteger('product_order_item_id')->nullable()->after('product_order_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::table('product_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('product_reviews', 'product_order_item_id')) {
                $table->dropColumn('product_order_item_id');
            }
            if (Schema::hasColumn('product_reviews', 'product_order_id')) {
                $table->dropColumn('product_order_id');
            }
        });
    }
};
