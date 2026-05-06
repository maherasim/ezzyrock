<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_id')->nullable()->after('max_purchase_qty');
            $table->foreign('product_unit_id')->references('id')->on('product_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_unit_id']);
            $table->dropColumn('product_unit_id');
        });
    }
};
