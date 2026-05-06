<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fixes 403 on Product/Post admin: ensures permissions exist and are granted to admin + provider roles.
 * Run once: php artisan db:seed --class=AssignProductPostPermissionsSeeder
 */
class AssignProductPostPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $serviceRow = DB::table('permissions')->where('name', 'service')->whereNull('parent_id')->first();
        $parentId = $serviceRow ? $serviceRow->id : DB::table('permissions')->where('name', 'service list')->value('parent_id');

        $perms = [
            ['product list', 'product add', 'product edit', 'product delete'],
            ['post list', 'post add', 'post edit', 'post delete'],
        ];
        foreach (array_merge(...$perms) as $name) {
            if (! DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 1=admin, 2=demo_admin, 4=provider (see RoleTableSeeder)
        $roleIds = [1, 2, 4];
        foreach ($roleIds as $roleId) {
            if (! DB::table('roles')->where('id', $roleId)->exists()) {
                continue;
            }
            $permIds = DB::table('permissions')
                ->whereIn('name', [
                    'product list', 'product add', 'product edit', 'product delete',
                    'post list', 'post add', 'post edit', 'post delete',
                ])
                ->pluck('id');

            foreach ($permIds as $pid) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $pid)
                    ->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $pid,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        $this->command?->info('Product & Post permissions assigned to admin, demo_admin, and provider roles.');
    }
}
