<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates one sample category + subcategory per section (service / ecommerce / classified)
 * so each module has its own tree. Safe to run multiple times (skips if slug exists).
 */
class SectionCategoryTreeSeeder extends Seeder
{
    public function run(): void
    {
        $trees = [
            Category::MODULE_SERVICE => [
                'category' => ['name' => 'General Services', 'description' => 'Service bookings category'],
                'sub' => ['name' => 'General Repairs', 'description' => 'Service subcategory'],
            ],
            Category::MODULE_ECOMMERCE => [
                'category' => ['name' => 'General Store', 'description' => 'Product sales category'],
                'sub' => ['name' => 'Accessories', 'description' => 'Product subcategory'],
            ],
            Category::MODULE_CLASSIFIED => [
                'category' => ['name' => 'General Listings', 'description' => 'Classified ads category'],
                'sub' => ['name' => 'Misc Listings', 'description' => 'Classified subcategory'],
            ],
        ];

        foreach ($trees as $module => $tree) {
            $slug = Str::slug($tree['category']['name'].'-'.$module);
            $cat = Category::firstOrCreate(
                [
                    'module_type' => $module,
                    'name' => $tree['category']['name'],
                ],
                [
                    'description' => $tree['category']['description'],
                    'status' => 1,
                    'is_featured' => 0,
                    'slug' => $slug,
                    'seo_enabled' => false,
                ]
            );

            SubCategory::firstOrCreate(
                [
                    'category_id' => $cat->id,
                    'name' => $tree['sub']['name'],
                ],
                [
                    'description' => $tree['sub']['description'],
                    'status' => 1,
                    'is_featured' => 0,
                    'slug' => Str::slug($tree['sub']['name'].'-'.$module),
                    'seo_enabled' => false,
                ]
            );
        }
    }
}
