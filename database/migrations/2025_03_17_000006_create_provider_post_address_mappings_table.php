<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_post_address_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('provider_address_id')->nullable();
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('provider_address_id')->references('id')->on('provider_address_mappings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_post_address_mappings');
    }
};
