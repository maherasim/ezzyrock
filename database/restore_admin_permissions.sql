-- =============================================================================
-- Fix 403 on ALL admin pages (role_has_permissions was emptied or incomplete)
-- =============================================================================
-- Step A — ensure product/post permissions exist (run once via Artisan instead):
--   php artisan db:seed --class=AssignProductPostPermissionsSeeder
--
-- Step B — grant admin + demo_admin every permission currently in `permissions`:
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT id, 1 FROM permissions;

INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT id, 2 FROM permissions;
--
-- Step C — terminal:
--   php artisan permission:cache-reset
-- Log out and log in again.
-- =============================================================================
