<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\ServiceZone;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sample product orders + classified posts for a customer user (My orders / My posts UI testing).
 *
 * Primary account: {@see self::PRIMARY_CUSTOMER_EMAIL}. Falls back to demo@user.com, then any active customer.
 *
 * Run: php artisan db:seed --class=UserDemoOrdersAndClassifiedPostsSeeder
 */
class UserDemoOrdersAndClassifiedPostsSeeder extends Seeder
{
    /** Demo data is attached to this user when the account exists (user_type = user, active). */
    public const PRIMARY_CUSTOMER_EMAIL = 'nowsathnowsath93@gmail.com';

    public function run(): void
    {
        if (! Schema::hasTable('product_orders') || ! Schema::hasTable('posts')) {
            $this->command?->warn('product_orders or posts table missing — run migrations first.');

            return;
        }

        $user = User::query()
            ->where('user_type', 'user')
            ->where('status', 1)
            ->where('email', self::PRIMARY_CUSTOMER_EMAIL)
            ->first()
            ?? User::query()
                ->where('user_type', 'user')
                ->where('status', 1)
                ->where('email', 'demo@user.com')
                ->first()
            ?? User::query()->where('user_type', 'user')->where('status', 1)->orderBy('id')->first();

        if (! $user) {
            $this->command?->error('No active customer user found. Create a user with user_type = user first.');

            return;
        }

        $products = Product::query()
            ->where('service_type', 'ecommerce')
            ->where('status', 1)
            ->where('service_request_status', 'approve')
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            $this->command?->warn('No approved ecommerce products found. Run LandingSampleProductPostSeeder or add products first.');

            return;
        }

        $this->seedDemoOrders($user, $products);
        $this->seedDemoClassifiedPosts($user);
    }

    private function seedDemoOrders(User $user, $products): void
    {
        $prefix = 'DEMO-U'.$user->id.'-';
        $specs = [
            $prefix.'PO-0001' => [
                ['product' => $products[0], 'qty' => 1],
                ['product' => $products->get(1) ?? $products[0], 'qty' => 2],
            ],
            $prefix.'PO-0002' => [
                ['product' => $products->get(2) ?? $products[0], 'qty' => 1],
            ],
        ];

        foreach ($specs as $orderNumber => $lines) {
            if (ProductOrder::query()->where('order_number', $orderNumber)->exists()) {
                continue;
            }

            $subtotal = 0;
            $prepared = [];
            foreach ($lines as $line) {
                $p = $line['product'];
                if (! $p) {
                    continue;
                }
                $unit = (float) $p->price;
                if ($p->discount > 0) {
                    $unit = $unit - ($unit * (float) $p->discount / 100);
                }
                $unit = round($unit, 2);
                $qty = (int) $line['qty'];
                $lineTotal = round($unit * $qty, 2);
                $subtotal += $lineTotal;
                $prepared[] = [
                    'product' => $p,
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            if ($prepared === []) {
                continue;
            }

            $order = ProductOrder::query()->create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'status' => 'confirmed',
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
                'notes' => 'Seeded sample order for testing My orders.',
            ]);

            foreach ($prepared as $row) {
                $p = $row['product'];
                ProductOrderItem::query()->create([
                    'product_order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'unit_price' => $row['unit_price'],
                    'quantity' => $row['quantity'],
                    'line_total' => $row['line_total'],
                ]);
            }

            $this->command?->info("Created sample product order {$orderNumber} for user #{$user->id} ({$user->email}).");
        }
    }

    private function seedDemoClassifiedPosts(User $user): void
    {
        $category = Category::query()
            ->where('module_type', Category::MODULE_CLASSIFIED)
            ->where('status', 1)
            ->orderBy('id')
            ->first();

        if (! $category) {
            $this->command?->warn('No classified category found. Skipping demo posts.');

            return;
        }

        $sub = SubCategory::query()
            ->where('category_id', $category->id)
            ->where('status', 1)
            ->orderBy('id')
            ->first();

        $samples = [
            [
                'name' => 'Demo My Post: study desk (seed)',
                'slug_suffix' => 'study-desk',
                'price' => 75.00,
                'description' => 'Sample classified listing for testing My posts (customer-owned).',
                'img' => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=600&q=80',
            ],
            [
                'name' => 'Demo My Post: bicycle for sale (seed)',
                'slug_suffix' => 'bicycle',
                'price' => 140.00,
                'description' => 'Another sample listing under your account.',
                'img' => 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&q=80',
            ],
            [
                'name' => 'Demo My Post: bookshelf (seed)',
                'slug_suffix' => 'bookshelf',
                'price' => 40.00,
                'description' => 'Third sample listing for pagination/grid testing.',
                'img' => 'https://images.unsplash.com/photo-1594620302200-9a762244a156?w=600&q=80',
            ],
        ];

        foreach ($samples as $row) {
            $slug = 'demo-user-seed-'.$row['slug_suffix'].'-u'.$user->id;

            $post = Post::query()->where('provider_id', $user->id)->where('slug', $slug)->first();
            if ($post) {
                continue;
            }

            $post = Post::query()->create([
                'name' => $row['name'],
                'category_id' => $category->id,
                'subcategory_id' => $sub?->id,
                'description' => $row['description'],
                'price' => $row['price'],
                'type' => 'fixed',
                'status' => 1,
                'visit_type' => 'on_site',
                'duration' => null,
                'discount' => 0,
                'provider_id' => $user->id,
                'added_by' => $user->id,
                'service_type' => 'classified',
                'service_request_status' => 'approve',
                'is_service_request' => 0,
                'is_featured' => 0,
                'is_slot' => 0,
                'is_enable_advance_payment' => 0,
                'advance_payment_amount' => null,
                'slug' => $slug,
            ]);

            $this->attachImage($post, 'post_attachment', $row['img']);

            $zoneIds = ServiceZone::query()->where('status', true)->pluck('id')->all();
            if ($zoneIds !== []) {
                $post->zones()->sync($zoneIds);
            }

            $this->command?->info("Created sample classified post \"{$row['name']}\" for user #{$user->id}.");
        }
    }

    private function attachImage($model, string $collection, string $url): void
    {
        if ($model->getMedia($collection)->isNotEmpty()) {
            return;
        }
        try {
            $model->addMediaFromUrl($url)
                ->usingFileName('demo-seed-'.Str::random(8).'.jpg')
                ->toMediaCollection($collection);

            return;
        } catch (\Throwable $e) {
        }
        if (extension_loaded('gd')) {
            $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'demo-seed-'.uniqid('', true).'.jpg';
            $im = imagecreatetruecolor(600, 400);
            $bg = imagecolorallocate($im, 95, 158, 160);
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
}
