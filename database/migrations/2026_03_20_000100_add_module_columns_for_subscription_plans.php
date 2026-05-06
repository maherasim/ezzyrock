<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'module')) {
                $table->string('module', 32)->default('service')->after('plan_type');
            }
        });

        Schema::table('provider_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('provider_subscriptions', 'module')) {
                $table->string('module', 32)->nullable()->after('plan_type');
            }
        });

        DB::table('plans')->whereNull('module')->update(['module' => 'service']);

        DB::statement("
            UPDATE provider_subscriptions ps
            INNER JOIN plans p ON p.id = ps.plan_id
            SET ps.module = p.module
            WHERE ps.module IS NULL
        ");

        DB::table('provider_subscriptions')->whereNull('module')->update(['module' => 'service']);
    }

    public function down(): void
    {
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('provider_subscriptions', 'module')) {
                $table->dropColumn('module');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'module')) {
                $table->dropColumn('module');
            }
        });
    }
};
