<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 32)->default('confirmed')->comment('pending, confirmed, cancelled');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('product_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->foreign('product_order_id', 'poi_order_fk')->references('id')->on('product_orders')->onDelete('cascade');
            $table->foreign('product_id', 'poi_product_fk')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_order_items');
        Schema::dropIfExists('product_orders');
    }
};
