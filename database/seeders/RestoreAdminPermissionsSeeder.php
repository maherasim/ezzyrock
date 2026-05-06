<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * EMERGENCY FIX: Admin gets 403 on all pages when role_has_permissions was wiped
 * (e.g. RoleHasPermissionsTableSeeder failed after DELETE because product/post
 * permission rows 169–176 were missing — entire INSERT failed, table left empty).
 *
 * Run:
 *   php artisan db:seed --class=RestoreAdminPermissionsSeeder
 *   php artisan permission:cache-reset
 *
 * Then log out and log back in.
 */
class RestoreAdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure product/post permissions exist + assign to provider (4)
        $this->call(AssignProductPostPermissionsSeeder::class);

        $permIds = DB::table('permissions')->pluck('id');
        if ($permIds->isEmpty()) {
            $this->command?->error('No rows in permissions table. Run PermissionTableSeeder first.');

            return;
        }

        // Grant every permission to admin (1) and demo_admin (2)
        foreach ([1, 2] as $roleId) {
            if (! DB::table('roles')->where('id', $roleId)->exists()) {
                continue;
            }
            foreach ($permIds as $pid) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $pid,
                    'role_id' => $roleId,
                ]);
            }
        }

        $this->command?->info('Roles 1 (admin) and 2 (demo_admin) now have all permissions. Run: php artisan permission:cache-reset');
    }
}
