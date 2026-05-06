<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_attribute_id');
            $table->string('value');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_attribute_id')->references('id')->on('product_attributes')->onDelete('cascade');
            $table->unique(['product_attribute_id', 'value']);
        });

        Schema::create('product_attribute_option_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_attribute_id');
            $table->unsignedBigInteger('product_attribute_option_id');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_attribute_id')->references('id')->on('product_attributes')->onDelete('cascade');
            $table->foreign('product_attribute_option_id')->references('id')->on('product_attribute_options')->onDelete('cascade');
            $table->unique(['product_id', 'product_attribute_option_id'], 'product_attr_option_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_option_product');
        Schema::dropIfExists('product_attribute_options');
        Schema::dropIfExists('product_attributes');
    }
};

