<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_plan', function (Blueprint $table) {
            if (! Schema::hasColumn('user_plan', 'free_posts')) {
                $table->unsignedInteger('free_posts')->default(0)->after('trial_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_plan', function (Blueprint $table) {
            if (Schema::hasColumn('user_plan', 'free_posts')) {
                $table->dropColumn('free_posts');
            }
        });
    }
};
