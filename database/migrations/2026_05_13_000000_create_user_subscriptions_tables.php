<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('title');
            $table->string('identifier');
            $table->string('type');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->double('amount')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->text('plan_limitation')->nullable();
            $table->text('duration')->nullable();
            $table->longText('description')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('module', 32)->nullable();
            $table->string('active_in_app_purchase_identifier')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('user_plan')->onDelete('cascade');
            $table->index('user_id');
            $table->index(['status', 'module']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
