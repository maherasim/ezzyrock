<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');

            $table->dropUnique(['user_id', 'product_id']);
            $table->unique(['user_id', 'product_id', 'product_variant_id'], 'product_cart_items_user_product_variant_unique');
        });

        Schema::table('product_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
            $table->string('variant_label')->nullable()->after('product_name');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('product_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn(['product_variant_id', 'variant_label']);
        });

        Schema::table('product_cart_items', function (Blueprint $table) {
            $table->dropUnique('product_cart_items_user_product_variant_unique');
            $table->unique(['user_id', 'product_id']);
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });
    }
};

