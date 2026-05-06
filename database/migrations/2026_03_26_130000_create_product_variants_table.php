<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_attribute_option_id');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('max_purchase_qty')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_attribute_option_id')->references('id')->on('product_attribute_options')->onDelete('cascade');
            $table->unique(['product_id', 'product_attribute_option_id'], 'product_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

