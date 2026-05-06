<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Post;
use App\Models\Setting;
use App\Models\ServiceZone;
use Illuminate\Http\Request;
use App\Traits\TranslationTrait;
use App\Traits\NotificationTrait;
use Yajra\DataTables\DataTables;
use App\Models\PostZoneMapping;
use Illuminate\Support\Str;
use App\Models\ProviderZoneMapping;
use App\Http\Requests\PostRequest;

class PostController extends Controller
{
    use TranslationTrait, NotificationTrait;

    public function index(Request $request)
    {
        $auth_user = auth()->user();
        $filter = ['status' => $request->status];
        $pageTitle = __('messages.all_form_title', ['form' => __('messages.posts')]);
        $assets = ['datatable'];
        $zone_id = $request->zone_id;
        $globalSeoSetting = \App\Models\SeoSetting::first();
        return view('post.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'zone_id', 'globalSeoSetting'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Post::query()->where('service_request_status', 'approve')->myPost();
        $primary_locale = app()->getLocale() ?? 'en';
        $filter = $request->filter;

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }
        if (auth()->user()->hasAnyRole(['admin', 'provider'])) {
            $query = $query->where('service_type', 'classified')->withTrashed();
        }
        if ($request->has('zone_id') && $request->zone_id != null) {
            $query->whereHas('postZoneMapping', function ($q) use ($request) {
                $q->where('zone_id', $request->zone_id);
            });
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $row->id . '" name="datatable_ids[]" value="' . $row->id . '" data-type="post" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('name', function ($query) use ($primary_locale) {
                $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?? $query->name;
                $imageUrls = getSingleMedia($query, 'post_attachment', null);
                if (!is_array($imageUrls)) {
                    $imageUrls = [$imageUrls];
                }
                $imageTags = '';
                foreach ($imageUrls as $imageUrl) {
                    $imageTags .= '<img src="' . $imageUrl . '" alt="post image" class="avatar avatar-40 rounded-pill mr-2">';
                }
                if (auth()->user()->can('post edit')) {
                    $link = '<a class="btn-link btn-link-hover" href="' . route('post.create', ['id' => $query->id]) . '">' . $name . '</a>';
                } else {
                    $link = $name;
                }
                return '<div style="display: flex; align-items: center;">' . $imageTags . '<span style="margin-left: 10px;">' . $link . '</span></div>';
            })
            ->filterColumn('name', function ($query, $keyword) use ($primary_locale) {
                if ($primary_locale !== 'en') {
                    $query->where(function ($q) use ($keyword, $primary_locale) {
                        $q->whereHas('translations', function ($q) use ($keyword, $primary_locale) {
                            $q->where('locale', $primary_locale)->where('value', 'LIKE', '%' . $keyword . '%');
                        })->orWhere('name', 'LIKE', '%' . $keyword . '%');
                    });
                } else {
                    $query->where('name', 'LIKE', '%' . $keyword . '%');
                }
            })
            ->editColumn('category_id', function ($query) {
                $category = $query->category;
                return $category ? e($category->name) : '-';
            })
            ->addColumn('posted_by', function ($query) {
                return optional($query->providers)->display_name ?? optional($query->providers)->email ?? '-';
            })
            ->filterColumn('posted_by', function ($query, $keyword) {
                $query->whereHas('providers', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('posted_by', function ($query, $order) {
                $query->select('posts.*')
                    ->leftJoin('users as post_users', 'post_users.id', '=', 'posts.provider_id')
                    ->orderBy('post_users.display_name', $order);
            })
            ->filterColumn('category_id', function ($query, $keyword) use ($primary_locale) {
                $query->whereHas('category', function ($q) use ($keyword, $primary_locale) {
                    if ($primary_locale !== 'en') {
                        $q->where(function ($q) use ($keyword, $primary_locale) {
                            $q->whereHas('translations', function ($q) use ($keyword, $primary_locale) {
                                $q->where('locale', $primary_locale)->where('value', 'LIKE', '%' . $keyword . '%');
                            })->orWhere('name', 'LIKE', '%' . $keyword . '%');
                        });
                    } else {
                        $q->where('name', 'LIKE', '%' . $keyword . '%');
                    }
                });
            })
            ->orderColumn('category_id', function ($query, $order) {
                $query->join('categories', 'categories.id', '=', 'posts.category_id')->orderBy('categories.name', $order);
            })
            ->editColumn('price', function ($query) {
                return getPriceFormat($query->price) . '-' . ucFirst($query->type);
            })
            ->editColumn('discount', function ($query) {
                return $query->discount ? $query->discount . '%' : '-';
            })
            ->addColumn('action', function ($data) {
                return view('post.action', compact('data'));
            })
            ->editColumn('status', function ($query) {
                $disabled = $query->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="post_status" ' . ($query->status ? "checked" : "") . ' ' . $disabled . ' value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->rawColumns(['action', 'status', 'check', 'name'])
            ->toJson();
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('post add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        if (! $request->filled('id') && auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return redirect()->route('post.index')->withErrors(__('messages.admin_cannot_create_post'));
        }
        $id = $request->id;
        $auth_user = authSession();
        if (!$auth_user) {
            return redirect()->route('login')->withErrors(trans('messages.please_login'));
        }
        $language_array = $this->languagesArray();
        $postdata = null;
        if ($id) {
            $postdata = Post::with(['category', 'subcategory', 'providers', 'providerPostAddress', 'zones'])->find($id);
        }
        $zoneOwnerId = $postdata?->provider_id ?: $auth_user->id;
        $serviceZones = ProviderZoneMapping::with('zone')
            ->where('provider_id', $zoneOwnerId)
            ->get()
            ->filter(fn ($mapping) => $mapping->zone !== null)
            ->map(function ($mapping) {
                return [
                    'id' => $mapping->zone->id,
                    'name' => $mapping->zone->name,
                    'provider_id' => $mapping->provider_id,
                    'zone_id' => $mapping->zone_id,
                ];
            });
        $selectedZones = [];
        if ($postdata && $postdata->zones) {
            $selectedZones = $postdata->zones->pluck('id')->toArray();
        }
        $visittype = config('constant.VISIT_TYPE');
        $settingdata = Setting::where('type', '=', 'service-configurations')->first();
        $advancedPaymentSetting = 0;
        $slotservice = 0;
        $digital_services = 0;
        if ($settingdata) {
            $settings = json_decode($settingdata->value, true);
            $advancedPaymentSetting = $settings['advance_payment'] ?? 0;
            $slotservice = $settings['slot_service'] ?? 0;
            $digital_services = $settings['digital_services'] ?? 0;
        }
        if ($digital_services == 1) {
            $visittype = ['on_site' => 'On Site', 'on_shop' => 'On Shop', 'ONLINE' => 'Online'];
        } else {
            $visittype = ['ON_SITE' => 'On Site'];
        }
        $pageTitle = __('messages.update_form_title', ['form' => __('messages.post')]);
        if ($postdata == null) {
            $pageTitle = __('messages.add_button_form', ['form' => __('messages.post')]);
            $postdata = new Post;
        } else {
            if ($postdata->provider_id !== auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
                return redirect(route('post.index'))->withErrors(trans('messages.demo_permission_denied'));
            }
        }
        $globalSeoSetting = \App\Models\SeoSetting::first();
        return view('post.create', compact('language_array', 'pageTitle', 'postdata', 'auth_user', 'advancedPaymentSetting', 'visittype', 'slotservice', 'serviceZones', 'selectedZones', 'globalSeoSetting'));
    }

    public function store(PostRequest $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        if (! $request->filled('id') && auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return redirect()->route('post.index')->withErrors(__('messages.admin_cannot_create_post'));
        }
        $data = $request->except('seo_image');
        $data['seo_enabled'] = $request->has('seo_enabled') ? $request->seo_enabled : 0;
        if ($request->filled('meta_title')) {
            $data['slug'] = Str::slug($request->meta_title);
        }
        $language_option = sitesetupSession('get')->language_option ?? ["ar", "nl", "en", "fr", "de", "hi", "it"];
        $primary_locale = app()->getLocale() ?? 'en';
        $translatableAttributes = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
        $data['service_type'] = 'classified';
        $existingPost = $request->filled('id') ? Post::find($request->id) : null;
        $data['provider_id'] = $existingPost?->provider_id ?: auth()->user()->id;
        if ($request->id == null) {
            $classifiedMsg = plan_limit_user_message(get_provider_plan_limit($data['provider_id'], 'classified'), __('messages.posts'));
            if ($classifiedMsg !== null) {
                return redirect()->back()->withErrors($classifiedMsg);
            }
        }
        if ($request->id == null) {
            $data['added_by'] = $request->added_by ?: auth()->user()->id;
            $data['is_service_request'] = 1;
            if (auth()->user()->hasRole('demo_admin') || auth()->user()->hasRole('admin')) {
                $data['service_request_status'] = 'approve';
                $data['is_service_request'] = 0;
            }
        }
        $data['provider_id'] = $data['provider_id'] ?: auth()->user()->id;
        if (!$request->is('api/*')) {
            $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
            // Slot / advance payment toggles removed from admin post form — keep existing DB values on update
            if ($request->has('is_slot')) {
                $data['is_slot'] = $request->boolean('is_slot') ? 1 : 0;
            } elseif (!$existingPost) {
                $data['is_slot'] = 0;
            } else {
                unset($data['is_slot']);
            }
            if ($request->has('is_enable_advance_payment')) {
                $data['is_enable_advance_payment'] = $request->boolean('is_enable_advance_payment') ? 1 : 0;
            } elseif (!$existingPost) {
                $data['is_enable_advance_payment'] = 0;
            } else {
                unset($data['is_enable_advance_payment']);
            }
        }
        if (!empty($data['is_featured']) && (int) $data['is_featured'] === 1) {
            $featuredMsg = plan_limit_user_message(get_provider_plan_limit($data['provider_id'], 'featured_classified'), 'Featured posts');
            if ($featuredMsg !== null) {
                return redirect()->back()->withErrors($featuredMsg)->withInput();
            }
        }
        if ($request->filled('advance_payment_amount')) {
            $data['advance_payment_amount'] = $request->advance_payment_amount;
        }
        $result = Post::updateOrCreate(['id' => $request->id], $data);
        if (isset($data['translations']) && is_array($data['translations'])) {
            $result->saveTranslations($data, $translatableAttributes, $language_option, $primary_locale);
        }
        if ($result->providerPostAddress()->count() > 0) {
            $result->providerPostAddress()->delete();
        }
        if ($request->provider_address_id != null) {
            foreach ((array) $request->provider_address_id as $address) {
                $result->providerPostAddress()->create(['provider_address_id' => $address]);
            }
        }
        if ($request->has('service_zones')) {
            $serviceZones = is_string($request->service_zones) ? json_decode($request->service_zones, true) : $request->service_zones;
            if (is_array($serviceZones)) {
                $result->zones()->detach();
                $validZones = ServiceZone::query()
                    ->where('status', true)
                    ->whereIn('id', array_map('intval', $serviceZones))
                    ->pluck('id')
                    ->all();
                foreach ($validZones as $zoneId) {
                    PostZoneMapping::create(['post_id' => $result->id, 'zone_id' => $zoneId]);
                }
            }
        }
        if ($request->has('shop_ids')) {
            $shopIds = is_string($request->shop_ids) ? json_decode($request->shop_ids, true) : $request->shop_ids;
            $shopIds = array_filter(array_map('intval', (array) $shopIds));
            $result->shops()->sync($shopIds);
        }
        if ($request->hasFile('seo_image')) {
            storeMediaFile($result, $request->file('seo_image'), 'seo_image');
        }
        if ($request->hasFile('post_attachment')) {
            storeMediaFile($result, $request->file('post_attachment'), 'post_attachment');
        } elseif ($request->id && !getMediaFileExit($result, 'post_attachment')) {
            // keep existing
        } elseif (!$request->id && !getMediaFileExit($result, 'post_attachment')) {
            return redirect()->route('post.create', ['id' => $result->id])->withErrors(['post_attachment' => 'The attachment field is required.'])->withInput();
        }
        $message = $result->wasRecentlyCreated ? __('messages.save_form', ['form' => __('messages.post')]) : __('messages.update_form', ['form' => __('messages.post')]);
        return redirect(route('post.index'))->withSuccess($message);
    }

    public function show($id)
    {
        $locale = app()->getLocale();
        $post = Post::findOrFail($id);
        $globalSeoSetting = \App\Models\SeoSetting::first();
        $metaTitle = $post->translate('meta_title', $locale) ?? $post->meta_title ?? $post->name;
        $metaDescription = $post->translate('meta_description', $locale) ?? $post->meta_description ?? '';
        $metaKeywords = $post->translate('meta_keywords', $locale) ?? $post->meta_keywords ?? '';
        $slug = $post->slug ?? '';
        $seoImage = $post->getFirstMediaUrl('seo_image');
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.post')]);
        return view('post.view', compact('post', 'metaTitle', 'metaDescription', 'metaKeywords', 'slug', 'seoImage', 'pageTitle', 'globalSeoSetting'));
    }

    public function destroy($id)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $post = Post::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.post')]);
        if ($post) {
            $post->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.post')]);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    public function action(Request $request)
    {
        $post = Post::withTrashed()->find($request->id);
        $msg = __('messages.not_found_entry', ['name' => __('messages.post')]);
        if ($post) {
            if ($request->type === 'restore') {
                $post->restore();
                $msg = __('messages.msg_restored', ['name' => __('messages.post')]);
            }
            if ($request->type === 'forcedelete') {
                $post->forceDelete();
                $msg = __('messages.msg_forcedelete', ['name' => __('messages.post')]);
            }
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';
        switch ($actionType) {
            case 'change-status':
                Post::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_service_status_updated');
                break;
            case 'delete':
                Post::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_service_deleted');
                break;
            case 'restore':
                Post::whereIn('id', $ids)->restore();
                $message = __('messages.bulk_service_restored');
                break;
            case 'permanently-delete':
                Post::whereIn('id', $ids)->forceDelete();
                $message = __('messages.bulk_service_permanently_deleted');
                break;
            default:
                return response()->json(['status' => false, 'message' => __('messages.action_invalid')]);
        }
        return response()->json(['status' => true, 'message' => $message]);
    }

    public function getShops(Request $request)
    {
        $shops = Shop::where('provider_id', $request->provider_id)->select('id', 'shop_name')->get();
        return response()->json(['status' => true, 'data' => $shops]);
    }

    public function updateStatus(Request $request)
    {
        $post = Post::find($request->id);
        if ($post) {
            $post->service_request_status = ($request->status == 'approved') ? 'approve' : 'reject';
            $post->reject_reason = $request->reason;
            $post->save();
            $provider = User::find($post->provider_id);
            $activity_data = [
                'activity_type' => ($request->status == 'approved') ? 'service_request_approved' : 'service_request_reject',
                'service_id' => $post->id,
                'id' => $post->id,
                'provider_id' => $post->provider_id,
                'provider_name' => $provider->display_name ?? 'Unknown User',
                'user_name' => $provider?->display_name ?? 'Unknown User',
                'service_name' => $post->name,
                'reason' => $request->reason,
            ];
            $this->sendNotification($activity_data);

            return response()->json([
                'success' => true,
                'status' => $request->status,
                'serviceId' => $post->id,
                'providerId' => $post->provider_id,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Post not found']);
    }
}
