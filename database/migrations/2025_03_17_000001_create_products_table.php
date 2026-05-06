<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('provider_id');
            $table->double('price')->nullable()->default(0);
            $table->string('type')->nullable()->default('fixed')->comment('fixed, hourly');
            $table->string('duration')->nullable();
            $table->double('discount')->nullable()->comment('in percentage');
            $table->tinyInteger('status')->nullable()->default(1);
            $table->text('description')->nullable();
            $table->tinyInteger('is_featured')->nullable()->default(0);
            $table->bigInteger('added_by')->nullable();
            $table->string('service_request_status')->nullable()->default('pending');
            $table->tinyInteger('is_service_request')->nullable()->default(0);
            $table->longText('reject_reason')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('service_type')->nullable()->default('ecommerce');
            $table->tinyInteger('is_slot')->nullable()->default(0);
            $table->tinyInteger('is_enable_advance_payment')->nullable()->default(0);
            $table->double('advance_payment_amount')->nullable();
            $table->string('visit_type')->nullable()->default('on_site');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('seo_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('subcategory_id')->references('id')->on('sub_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
