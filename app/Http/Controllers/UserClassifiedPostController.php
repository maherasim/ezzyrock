<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\ServiceZone;
use App\Models\SubCategory;
use App\Services\FeaturedPostQuotaService;
use App\Traits\ZoneTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserClassifiedPostController extends Controller
{
    use ZoneTrait;

    public function __construct()
    {
        $this->middleware('auth')->except(['createIntent']);
    }

    public function index()
    {
        $this->ensureCustomer();
        $quotaService = app(FeaturedPostQuotaService::class);
        $freePostQuota = $quotaService->getFreePostQuota(auth()->id());
        $featuredPostQuota = $quotaService->getFeaturedQuota(auth()->id());
        $posts = Post::query()
            ->where('provider_id', auth()->id())
            ->where('service_type', 'classified')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('landing-page.user-my-posts', compact('posts', 'freePostQuota', 'featuredPostQuota'));
    }

    public function create()
    {
        $this->ensureCustomer();
        $post = new Post;

        return view('landing-page.user-post-form', $this->postFormData($post, __('messages.add_button_form', ['form' => __('messages.post')])));
    }

    public function createIntent()
    {
        if (auth()->check()) {
            return redirect()->route('user.my-posts.create');
        }
        session(['post_login_redirect' => route('user.my-posts.create')]);

        return redirect()->route('user.login');
    }

    public function store(Request $request)
    {
        $this->ensureCustomer();
        $userId = auth()->id();
        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('posts', 'name')->where(fn ($q) => $q->where('provider_id', $userId)),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('module_type', 'classified')->where('status', 1)),
            ],
            'subcategory_id' => [
                'nullable',
                Rule::exists('sub_categories', 'id')->where(fn ($q) => $q->where('category_id', (int) $request->category_id)),
            ],
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'is_featured' => 'nullable|boolean',
            'post_attachment' => 'required|array|min:1',
            'post_attachment.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'service_zones' => 'required|array|min:1',
            'service_zones.*' => ['integer', Rule::exists('service_zones', 'id')->where(fn ($q) => $q->where('status', true))],
        ]);
        $isFeatured = (int) ($request->boolean('is_featured') ? 1 : 0);
        $quotaService = app(FeaturedPostQuotaService::class);
        $freePostQuota = $quotaService->getFreePostQuota($userId);
        $featuredPostQuota = $quotaService->getFeaturedQuota($userId);

        if (! $isFeatured && ! $freePostQuota['allow_to_create_post']) {
            return redirect()->back()
                ->withInput()
                ->withErrors('Your free post limit is finished for this month. Please wait for next month or purchase a plan to create featured posts.');
        }

        if ($isFeatured && ! $featuredPostQuota['allow_to_create_featured']) {
            return redirect()->back()
                ->withInput()
                ->withErrors('Please purchase a plan to create featured posts.');
        }

        $data = [
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'type' => 'fixed',
            'status' => 1,
            'visit_type' => 'on_site',
            'duration' => null,
            'discount' => 0,
            'provider_id' => $userId,
            'added_by' => $userId,
            'service_type' => 'classified',
            'service_request_status' => 'approve',
            'is_service_request' => 0,
            'is_featured' => $isFeatured,
            'is_slot' => 0,
            'is_enable_advance_payment' => 0,
            'advance_payment_amount' => null,
            'slug' => Str::slug($validated['name']).'-'.Str::random(4),
        ];

        $post = Post::create($data);

        if ($request->hasFile('post_attachment')) {
            storeMediaFile($post, $request->file('post_attachment'), 'post_attachment');
        }

        $this->syncSelectedZones($post, (array) ($validated['service_zones'] ?? []));

        return redirect()->route('user.my-posts')->with('success', __('messages.save_form', ['form' => __('messages.post')]));
    }

    private function syncSelectedZones(Post $post, array $zoneIds): void
    {
        $validZoneIds = ServiceZone::query()
            ->where('status', true)
            ->whereIn('id', $zoneIds)
            ->pluck('id')
            ->all();
        $post->zones()->sync($validZoneIds);
    }

    public function edit(Post $post)
    {
        $this->ensureCustomer();
        $this->authorizePost($post);

        return view('landing-page.user-post-form', $this->postFormData($post, __('messages.update_form_title', ['form' => __('messages.post')])));
    }

    public function update(Request $request, Post $post)
    {
        $this->ensureCustomer();
        $this->authorizePost($post);
        $userId = auth()->id();

        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('posts', 'name')
                    ->ignore($post->id)
                    ->where(fn ($q) => $q->where('provider_id', $userId)),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('module_type', 'classified')->where('status', 1)),
            ],
            'subcategory_id' => [
                'nullable',
                Rule::exists('sub_categories', 'id')->where(fn ($q) => $q->where('category_id', (int) $request->category_id)),
            ],
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'is_featured' => 'nullable|boolean',
            'post_attachment' => 'nullable|array',
            'post_attachment.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'service_zones' => 'required|array|min:1',
            'service_zones.*' => ['integer', Rule::exists('service_zones', 'id')->where(fn ($q) => $q->where('status', true))],
        ]);
        $isFeatured = (int) ($request->boolean('is_featured') ? 1 : 0);
        $quotaService = app(FeaturedPostQuotaService::class);
        $freePostQuota = $quotaService->getFreePostQuota($userId, $post->id);
        $featuredPostQuota = $quotaService->getFeaturedQuota($userId, $post->id);
        $needsFeaturedSlot = $isFeatured && (int) $post->is_featured !== 1;

        if ($needsFeaturedSlot && ! $featuredPostQuota['allow_to_create_featured']) {
            return redirect()->back()
                ->withInput()
                ->withErrors('Please purchase a plan to create featured posts.');
        }

        $post->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'is_featured' => $isFeatured,
        ]);

        if ($request->hasFile('post_attachment')) {
            storeMediaFile($post, $request->file('post_attachment'), 'post_attachment');
        }

        $this->syncSelectedZones($post, (array) ($validated['service_zones'] ?? []));

        return redirect()->route('user.my-posts')->with('success', __('messages.update_form', ['form' => __('messages.post')]));
    }

    public function destroy(Post $post)
    {
        $this->ensureCustomer();
        $this->authorizePost($post);
        $post->delete();

        return redirect()->route('user.my-posts')->with('success', __('messages.delete_form', ['form' => __('messages.post')]));
    }

    private function authorizePost(Post $post): void
    {
        abort_unless(
            $post->provider_id === auth()->id() && $post->service_type === 'classified',
            403
        );
    }

    private function ensureCustomer(): void
    {
        abort_unless(auth()->user()->user_type === 'user', 403);
    }

    private function postFormData(Post $post, string $pageTitle): array
    {
        $categories = Category::query()
            ->where('module_type', 'classified')
            ->where('status', 1)
            ->orderByDesc('is_featured')
            ->orderBy('id')
            ->get();

        $subcategories = SubCategory::query()
            ->where('status', 1)
            ->whereHas('category', fn ($q) => $q->where('module_type', 'classified'))
            ->orderByDesc('is_featured')
            ->orderBy('id')
            ->get();

        $subcategoriesByCategory = $subcategories->groupBy('category_id')->map(function ($group) {
            return $group->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values();
        });

        $zones = ServiceZone::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $existingZoneIds = $post->exists ? $post->zones()->pluck('service_zones.id')->all() : [];
        $selectedZoneIds = old('service_zones', $existingZoneIds ?: $this->defaultZoneIdsFromUserLocation());
        $quotaService = app(FeaturedPostQuotaService::class);

        return [
            'post' => $post,
            'pageTitle' => $pageTitle,
            'categories' => $categories,
            'subcategoriesByCategory' => $subcategoriesByCategory,
            'zones' => $zones,
            'selectedZoneIds' => array_map('intval', (array) $selectedZoneIds),
            'freePostQuota' => $quotaService->getFreePostQuota(auth()->id(), $post->exists ? $post->id : null),
            'featuredPostQuota' => $quotaService->getFeaturedQuota(auth()->id(), $post->exists ? $post->id : null),
        ];
    }

    private function defaultZoneIdsFromUserLocation(): array
    {
        $lat = session('user_lat');
        $lng = session('user_lng');
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return [];
        }

        try {
            return array_map('intval', $this->getMatchingZonesByLatLng($lat, $lng));
        } catch (\Throwable $e) {
            return [];
        }
    }
}
