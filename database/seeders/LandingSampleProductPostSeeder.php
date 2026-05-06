<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class LandingSampleProductPostSeeder extends Seeder
{
    /** Minimum mock items per type for landing grid testing (6 + 6 rows). */
    private const MOCK_COUNT = 18;

    /**
     * Sample ecommerce products + classified posts for the landing page.
     * Uses module categories (ecommerce / classified) and subcategories under each category.
     */
    public function run(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('posts')) {
            $this->command?->warn('products/posts tables missing — run migrations first.');

            return;
        }

        if (! Schema::hasColumn('categories', 'module_type')) {
            $this->command?->warn('Run migrations: categories.module_type is required.');

            return;
        }

        $provider = $this->resolveProvider();
        if (! $provider) {
            $this->command?->error('Could not resolve a provider user. Add an active provider first.');

            return;
        }

        $ecomCats = $this->ensureModuleCategories(Category::MODULE_ECOMMERCE, 'Ecommerce', 6);
        $classifiedCats = $this->ensureModuleCategories(Category::MODULE_CLASSIFIED, 'Classifieds', 6);

        $ecomSubMap = $this->ensureSubcategoriesForCategories($ecomCats, 'Ecommerce');
        $classifiedSubMap = $this->ensureSubcategoriesForCategories($classifiedCats, 'Classifieds');

        $imagePool = $this->imageUrlPool();

        $productRows = $this->buildProductRows($imagePool);
        foreach ($productRows as $i => $row) {
            $n = $i + 1;
            $slug = 'mock-landing-ecom-'.sprintf('%02d', $n);
            $cat = $ecomCats[$i % count($ecomCats)];
            $subs = $ecomSubMap[$cat->id] ?? [];
            $subId = $subs !== [] ? $subs[$i % count($subs)]->id : null;

            $product = Product::query()->where('slug', $slug)->first();
            $payload = [
                'name' => $row['name'],
                'category_id' => $cat->id,
                'subcategory_id' => $subId,
                'provider_id' => $provider->id,
                'price' => $row['price'],
                'description' => 'Mock ecommerce product for landing page grid testing (category + subcategory).',
                'status' => 1,
                'is_featured' => $i === 0 ? 1 : 0,
                'service_request_status' => 'approve',
                'is_service_request' => 0,
                'service_type' => 'ecommerce',
                'type' => 'fixed',
                'visit_type' => 'on_site',
                'slug' => $slug,
                'added_by' => $provider->id,
            ];
            if (! $product) {
                $product = Product::create($payload);
            } else {
                $product->update($payload);
            }
            $this->attachImage($product, 'product_attachment', $row['img']);
        }

        $postRows = $this->buildPostRows($imagePool);
        foreach ($postRows as $i => $row) {
            $n = $i + 1;
            $slug = 'mock-landing-classified-'.sprintf('%02d', $n);
            $cat = $classifiedCats[$i % count($classifiedCats)];
            $subs = $classifiedSubMap[$cat->id] ?? [];
            $subId = $subs !== [] ? $subs[$i % count($subs)]->id : null;

            $post = Post::query()->where('slug', $slug)->first();
            $payload = [
                'name' => $row['name'],
                'category_id' => $cat->id,
                'subcategory_id' => $subId,
                'provider_id' => $provider->id,
                'price' => $row['price'],
                'description' => 'Mock classified listing for landing page grid testing (category + subcategory).',
                'status' => 1,
                'is_featured' => $i === 0 ? 1 : 0,
                'service_request_status' => 'approve',
                'is_service_request' => 0,
                'service_type' => 'classified',
                'type' => 'fixed',
                'visit_type' => 'on_site',
                'slug' => $slug,
                'added_by' => $provider->id,
            ];
            if (! $post) {
                $post = Post::create($payload);
            } else {
                $post->update($payload);
            }
            $this->attachImage($post, 'post_attachment', $row['img']);
        }

        // Legacy sample slugs (sample-product-*, sample-post-*): refresh category/subcategory if present
        $this->syncLegacySamples($provider, $ecomCats, $classifiedCats, $ecomSubMap, $classifiedSubMap, $imagePool);

        $this->command?->info('Landing mock data: '.self::MOCK_COUNT.' ecommerce products + '.self::MOCK_COUNT.' classified posts (with subcategories).');
    }

    /**
     * @return array<int, array{name: string, price: float, img: string}>
     */
    private function buildProductRows(array $imagePool): array
    {
        $named = [
            ['name' => 'Wireless earbuds', 'price' => 49.99],
            ['name' => 'Desk lamp', 'price' => 35.00],
            ['name' => 'Ceramic mug set', 'price' => 24.50],
            ['name' => 'Backpack', 'price' => 79.00],
            ['name' => 'Plant pot', 'price' => 18.00],
            ['name' => 'USB-C hub', 'price' => 42.00],
            ['name' => 'Yoga mat', 'price' => 29.99],
            ['name' => 'Bluetooth speaker', 'price' => 65.00],
        ];
        $rows = [];
        for ($i = 0; $i < self::MOCK_COUNT; $i++) {
            if (isset($named[$i])) {
                $rows[] = [
                    'name' => $named[$i]['name'],
                    'price' => $named[$i]['price'],
                    'img' => $imagePool[$i % count($imagePool)],
                ];
            } else {
                $rows[] = [
                    'name' => 'Mock ecommerce product '.($i + 1),
                    'price' => round(12 + ($i * 6.25), 2),
                    'img' => $imagePool[$i % count($imagePool)],
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{name: string, price: float, img: string}>
     */
    private function buildPostRows(array $imagePool): array
    {
        $named = [
            ['name' => 'Used bicycle — good condition', 'price' => 120.00],
            ['name' => 'Sofa — moving sale', 'price' => 250.00],
            ['name' => 'Office chair', 'price' => 85.00],
            ['name' => 'Vintage camera', 'price' => 199.00],
            ['name' => 'Bookshelf', 'price' => 45.00],
            ['name' => 'Dining table set', 'price' => 320.00],
            ['name' => 'Mountain bike', 'price' => 275.00],
            ['name' => 'Kids scooter', 'price' => 35.00],
        ];
        $rows = [];
        for ($i = 0; $i < self::MOCK_COUNT; $i++) {
            if (isset($named[$i])) {
                $rows[] = [
                    'name' => $named[$i]['name'],
                    'price' => $named[$i]['price'],
                    'img' => $imagePool[($i + 5) % count($imagePool)],
                ];
            } else {
                $rows[] = [
                    'name' => 'Mock classified listing '.($i + 1),
                    'price' => round(20 + ($i * 11), 2),
                    'img' => $imagePool[($i + 5) % count($imagePool)],
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function imageUrlPool(): array
    {
        return [
            'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&q=80',
            'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600&q=80',
            'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=600&q=80',
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&q=80',
            'https://images.unsplash.com/photo-1485955900006-10f4d0d24ee4?w=600&q=80',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80',
            'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&q=80',
            'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=600&q=80',
            'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&q=80',
            'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=80',
            'https://images.unsplash.com/photo-1580480055272-228ff5388ef0?w=600&q=80',
            'https://images.unsplash.com/photo-1516035069371-29a1b244ccff?w=600&q=80',
            'https://images.unsplash.com/photo-1594620302200-9a762244a156?w=600&q=80',
            'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=600&q=80',
            'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=600&q=80',
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80',
        ];
    }

    private function syncLegacySamples(
        User $provider,
        array $ecomCats,
        array $classifiedCats,
        array $ecomSubMap,
        array $classifiedSubMap,
        array $imagePool
    ): void {
        $legacyProducts = [
            ['slug' => 'sample-product-1', 'name' => 'Wireless earbuds', 'price' => 49.99, 'img' => $imagePool[0]],
            ['slug' => 'sample-product-2', 'name' => 'Desk lamp', 'price' => 35.00, 'img' => $imagePool[1]],
            ['slug' => 'sample-product-3', 'name' => 'Ceramic mug set', 'price' => 24.50, 'img' => $imagePool[2]],
            ['slug' => 'sample-product-4', 'name' => 'Backpack', 'price' => 79.00, 'img' => $imagePool[3]],
            ['slug' => 'sample-product-5', 'name' => 'Plant pot', 'price' => 18.00, 'img' => $imagePool[4]],
        ];
        foreach ($legacyProducts as $i => $row) {
            $product = Product::query()->where('slug', $row['slug'])->first();
            if (! $product) {
                continue;
            }
            $cat = $ecomCats[$i % count($ecomCats)];
            $subs = $ecomSubMap[$cat->id] ?? [];
            $subId = $subs !== [] ? $subs[$i % count($subs)]->id : null;
            $product->update([
                'category_id' => $cat->id,
                'subcategory_id' => $subId,
            ]);
            $this->attachImage($product, 'product_attachment', $row['img']);
        }

        $legacyPosts = [
            ['slug' => 'sample-post-1', 'name' => 'Used bicycle — good condition', 'price' => 120.00, 'img' => $imagePool[10]],
            ['slug' => 'sample-post-2', 'name' => 'Sofa — moving sale', 'price' => 250.00, 'img' => $imagePool[11]],
            ['slug' => 'sample-post-3', 'name' => 'Office chair', 'price' => 85.00, 'img' => $imagePool[12]],
            ['slug' => 'sample-post-4', 'name' => 'Vintage camera', 'price' => 199.00, 'img' => $imagePool[13]],
            ['slug' => 'sample-post-5', 'name' => 'Bookshelf', 'price' => 45.00, 'img' => $imagePool[14]],
        ];
        foreach ($legacyPosts as $i => $row) {
            $post = Post::query()->where('slug', $row['slug'])->first();
            if (! $post) {
                continue;
            }
            $cat = $classifiedCats[$i % count($classifiedCats)];
            $subs = $classifiedSubMap[$cat->id] ?? [];
            $subId = $subs !== [] ? $subs[$i % count($subs)]->id : null;
            $post->update([
                'category_id' => $cat->id,
                'subcategory_id' => $subId,
            ]);
            $this->attachImage($post, 'post_attachment', $row['img']);
        }
    }

    /**
     * @param  array<int, Category>  $categories
     * @return array<int, array<int, SubCategory>>
     */
    private function ensureSubcategoriesForCategories(array $categories, string $label): array
    {
        $map = [];
        foreach ($categories as $ci => $cat) {
            $map[$cat->id] = [];
            for ($s = 1; $s <= 2; $s++) {
                $name = "{$cat->name} — Sub {$s}";
                $sub = SubCategory::query()
                    ->where('category_id', $cat->id)
                    ->where('name', $name)
                    ->first();
                if (! $sub) {
                    $sub = SubCategory::create([
                        'category_id' => $cat->id,
                        'name' => $name,
                        'description' => "Mock subcategory under {$label} category.",
                        'status' => 1,
                        'is_featured' => $s === 1 ? 1 : 0,
                    ]);
                }
                $map[$cat->id][] = $sub;
            }
        }

        return $map;
    }

    /**
     * @return array<int, Category>
     */
    private function ensureModuleCategories(string $module, string $label, int $count): array
    {
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            $name = "{$label} category {$i}";
            $cat = Category::query()
                ->where('module_type', $module)
                ->where('name', $name)
                ->first();
            if (! $cat) {
                $cat = Category::create([
                    'name' => $name,
                    'description' => "Categories for {$label} listings only.",
                    'status' => 1,
                    'is_featured' => $i <= 2 ? 1 : 0,
                    'module_type' => $module,
                ]);
            } else {
                $cat->update(['module_type' => $module]);
            }
            $out[] = $cat;
        }

        return $out;
    }

    private function attachImage($model, string $collection, string $url): void
    {
        if ($model->getMedia($collection)->isNotEmpty()) {
            return;
        }
        try {
            $model->addMediaFromUrl($url)
                ->usingFileName('landing-'.uniqid().'.jpg')
                ->toMediaCollection($collection);

            return;
        } catch (\Throwable $e) {
        }
        if (extension_loaded('gd')) {
            $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'landing-'.uniqid('', true).'.jpg';
            $im = imagecreatetruecolor(600, 400);
            $bg = imagecolorallocate($im, 70, 130, 180);
            imagefill($im, 0, 0, $bg);
            imagejpeg($im, $path, 85);
            imagedestroy($im);
            try {
                $model->addMedia($path)->toMediaCollection($collection);
            } finally {
                @unlink($path);
            }
        }
    }

    private function resolveProvider(): ?User
    {
        $q = User::query()->where('user_type', 'provider')->where('status', 1);
        if (function_exists('default_earning_type') && default_earning_type() === 'subscription') {
            $sub = (clone $q)->where('is_subscribe', 1)->first();
            if ($sub) {
                return $sub;
            }
        }

        $existing = (clone $q)->first();
        if ($existing) {
            return $existing;
        }

        $demo = User::firstOrCreate(
            ['email' => 'landing-demo-seller@sample.local'],
            [
                'username' => 'landing_demo_seller',
                'first_name' => 'Demo',
                'last_name' => 'Seller',
                'password' => bcrypt('password'),
                'user_type' => 'provider',
                'status' => 1,
                'display_name' => 'Demo Seller',
                'is_subscribe' => 1,
                'contact_number' => '0000000000',
            ]
        );

        $role = Role::query()->where('name', 'provider')->first();
        if ($role) {
            try {
                if (! $demo->hasRole($role)) {
                    $demo->assignRole($role);
                }
            } catch (\Throwable $e) {
            }
        }

        return $demo;
    }
}
