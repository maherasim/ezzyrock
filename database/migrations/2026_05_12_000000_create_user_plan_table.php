<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_plan', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('identifier');
            $table->string('playstore_identifier')->nullable();
            $table->string('appstore_identifier')->nullable();
            $table->string('type');
            $table->double('amount')->nullable();
            $table->tinyInteger('status')->nullable()->default('1');
            $table->text('duration')->nullable();
            $table->longtext('description')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('module', 32)->default('classified');
            $table->bigInteger('trial_period')->nullable();
            $table->unsignedInteger('free_posts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plan');
    }
};
