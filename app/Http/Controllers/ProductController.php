<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ServiceZone;
use Illuminate\Http\Request;
use App\Traits\TranslationTrait;
use App\Traits\NotificationTrait;
use Yajra\DataTables\DataTables;
use App\Models\ProductZoneMapping;
use Illuminate\Support\Str;
use App\Models\ProviderZoneMapping;
use App\Http\Requests\ProductRequest;

class ProductController extends Controller
{
    use TranslationTrait, NotificationTrait;

    public function index(Request $request)
    {
        $auth_user = auth()->user();
        $filter = ['status' => $request->status];
        $pageTitle = __('messages.all_form_title', ['form' => __('messages.products')]);
        $assets = ['datatable'];
        $zone_id = $request->zone_id;
        $globalSeoSetting = \App\Models\SeoSetting::first();
        return view('product.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'zone_id', 'globalSeoSetting'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Product::query()->where('service_request_status', 'approve')->myProduct();
        $primary_locale = app()->getLocale() ?? 'en';
        $filter = $request->filter;

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }
        if (auth()->user()->hasAnyRole(['admin', 'provider'])) {
            $query = $query->where('service_type', 'ecommerce')->withTrashed();
        }
        if ($request->has('zone_id') && $request->zone_id != null) {
            $query->whereHas('productZoneMapping', function ($q) use ($request) {
                $q->where('zone_id', $request->zone_id);
            });
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $row->id . '" name="datatable_ids[]" value="' . $row->id . '" data-type="product" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('name', function ($query) use ($primary_locale) {
                $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?? $query->name;
                $imageUrls = getSingleMedia($query, 'product_attachment', null);
                if (!is_array($imageUrls)) {
                    $imageUrls = [$imageUrls];
                }
                $imageTags = '';
                foreach ($imageUrls as $imageUrl) {
                    $imageTags .= '<img src="' . $imageUrl . '" alt="product image" class="avatar avatar-40 rounded-pill mr-2">';
                }
                if (auth()->user()->can('product edit')) {
                    $link = '<a class="btn-link btn-link-hover" href="' . route('product.create', ['id' => $query->id]) . '">' . $name . '</a>';
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
                $query->join('categories', 'categories.id', '=', 'products.category_id')->orderBy('categories.name', $order);
            })
            ->editColumn('provider_id', function ($query) {
                return view('product.product', compact('query'));
            })
            ->filterColumn('provider_id', function ($query, $keyword) {
                $query->whereHas('providers', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->select('products.*')->join('users as providers', 'providers.id', '=', 'products.provider_id')->orderBy('providers.display_name', $order);
            })
            ->editColumn('price', function ($query) {
                return getPriceFormat($query->price) . '-' . ucFirst($query->type);
            })
            ->editColumn('discount', function ($query) {
                return $query->discount ? $query->discount . '%' : '-';
            })
            ->addColumn('action', function ($data) {
                return view('product.action', compact('data'));
            })
            ->editColumn('status', function ($query) {
                $disabled = $query->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="product_status" ' . ($query->status ? "checked" : "") . ' ' . $disabled . ' value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->rawColumns(['action', 'status', 'check', 'name'])
            ->toJson();
    }

    public function create(Request $request)
    {
        if (!auth()->user()->can('product add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $auth_user = authSession();
        if (!$auth_user) {
            return redirect()->route('login')->withErrors(trans('messages.please_login'));
        }
        $language_array = $this->languagesArray();
        $productdata = null;
        if ($id) {
            $productdata = Product::with(['category', 'subcategory', 'providers', 'providerProductAddress', 'zones', 'attributeOptions'])->find($id);
        }
        $zonesForProvider = $auth_user->id;
        if ($productdata && $productdata->provider_id) {
            $zonesForProvider = $productdata->provider_id;
        } elseif ($auth_user->hasAnyRole(['admin', 'demo_admin']) && $request->filled('provider_id')) {
            $zonesForProvider = (int) $request->provider_id;
        }
        $serviceZones = ProviderZoneMapping::with('zone')
            ->where('provider_id', $zonesForProvider)
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
        if ($productdata && $productdata->zones) {
            $selectedZones = $productdata->zones->pluck('id')->toArray();
        }
        $attributeGroups = ProductAttribute::query()
            ->where('status', true)
            ->orderBy('name')
            ->get();
        $productUnits = ProductUnit::query()
            ->where('status', true)
            ->orderBy('name')
            ->get();
        $selectedProductAttributeId = null;
        $variantLabelDefaults = [];
        $productVariantMap = collect();
        if ($productdata && $productdata->exists) {
            $attachedOptions = $productdata->attributeOptions()->orderBy('product_attribute_options.id')->get();
            if ($attachedOptions->isNotEmpty()) {
                $attrIds = $attachedOptions->pluck('product_attribute_id')->unique()->filter();
                if ($attrIds->count() === 1) {
                    $selectedProductAttributeId = (int) $attrIds->first();
                }
            }
            $productVariantMap = $productdata->variants()->get()->keyBy('product_attribute_option_id')->map(function ($row) {
                return [
                    'price' => $row->price,
                    'stock' => $row->stock,
                    'max_purchase_qty' => $row->max_purchase_qty,
                    'status' => (int) $row->status,
                ];
            });
            foreach ($attachedOptions as $opt) {
                $vm = $productVariantMap->get($opt->id);
                $variantLabelDefaults[$opt->value] = [
                    'price' => $vm['price'] ?? $productdata->price ?? 0,
                    'stock' => $vm['stock'] ?? 0,
                ];
            }
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
        $pageTitle = __('messages.update_form_title', ['form' => __('messages.product')]);
        if ($productdata == null) {
            $pageTitle = __('messages.add_button_form', ['form' => __('messages.product')]);
            $productdata = new Product;
        } else {
            if ($productdata->provider_id !== auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
                return redirect(route('product.index'))->withErrors(trans('messages.demo_permission_denied'));
            }
        }
        $globalSeoSetting = \App\Models\SeoSetting::first();
        return view('product.create', compact('language_array', 'pageTitle', 'productdata', 'auth_user', 'advancedPaymentSetting', 'visittype', 'slotservice', 'serviceZones', 'selectedZones', 'globalSeoSetting', 'attributeGroups', 'productUnits', 'selectedProductAttributeId', 'variantLabelDefaults', 'productVariantMap'));
    }

    public function store(ProductRequest $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $existingProduct = $request->filled('id') ? Product::query()->find((int) $request->id) : null;
        $data = $request->except('seo_image');
        $data['seo_enabled'] = $request->has('seo_enabled') ? $request->seo_enabled : 0;
        if ($request->filled('meta_title')) {
            $data['slug'] = Str::slug($request->meta_title);
        }
        $language_option = sitesetupSession('get')->language_option ?? ["ar", "nl", "en", "fr", "de", "hi", "it"];
        $primary_locale = app()->getLocale() ?? 'en';
        $translatableAttributes = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
        $data['service_type'] = 'ecommerce';
        $data['provider_id'] = $request->provider_id ?: auth()->user()->id;
        if ($request->id == null) {
            $productMsg = plan_limit_user_message(get_provider_plan_limit($data['provider_id'], 'ecommerce'), __('messages.products'));
            if ($productMsg !== null) {
                return redirect()->back()->withErrors($productMsg);
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
            $data['is_slot'] = $request->has('is_slot') ? 1 : 0;
            $data['is_enable_advance_payment'] = $request->has('is_enable_advance_payment') ? 1 : 0;
        }
        $isFeaturedProduct = ! empty($data['is_featured']) && (int) $data['is_featured'] === 1;
        if ($isFeaturedProduct && ($existingProduct === null || (int) $existingProduct->is_featured !== 1)) {
            $featuredMsg = plan_limit_user_message(get_provider_plan_limit($data['provider_id'], 'featured_ecommerce'), 'Featured products');
            if ($featuredMsg !== null) {
                return redirect()->back()->withErrors($featuredMsg)->withInput();
            }
        }
        if ($request->filled('advance_payment_amount')) {
            $data['advance_payment_amount'] = $request->advance_payment_amount;
        }
        $data['product_unit_id'] = $request->filled('product_unit_id') ? (int) $request->product_unit_id : null;
        $result = Product::updateOrCreate(['id' => $request->id], $data);
        if (isset($data['translations']) && is_array($data['translations'])) {
            $result->saveTranslations($data, $translatableAttributes, $language_option, $primary_locale);
        }
        if ($result->providerProductAddress()->count() > 0) {
            $result->providerProductAddress()->delete();
        }
        if ($request->provider_address_id != null) {
            foreach ((array) $request->provider_address_id as $address) {
                $result->providerProductAddress()->create(['provider_address_id' => $address]);
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
                    ProductZoneMapping::create(['product_id' => $result->id, 'zone_id' => $zoneId]);
                }
            }
        }
        if ($request->has('shop_ids')) {
            $shopIds = is_string($request->shop_ids) ? json_decode($request->shop_ids, true) : $request->shop_ids;
            $shopIds = array_filter(array_map('intval', (array) $shopIds));
            $result->shops()->sync($shopIds);
        }

        $attributeId = (int) $request->input('product_attribute_id', 0);
        $variantLabels = array_values(array_filter(array_map('trim', (array) $request->input('variant_labels', []))));

        if ($attributeId > 0 && count($variantLabels) > 0) {
            $attribute = ProductAttribute::query()->where('id', $attributeId)->where('status', true)->first();
            if ($attribute) {
                $optionIds = [];
                foreach ($variantLabels as $label) {
                    $option = ProductAttributeOption::withTrashed()
                        ->where('product_attribute_id', $attributeId)
                        ->where('value', $label)
                        ->first();
                    if ($option) {
                        if ($option->trashed()) {
                            $option->restore();
                        }
                        if (!$option->status) {
                            $option->status = true;
                            $option->save();
                        }
                    } else {
                        $option = ProductAttributeOption::query()->create([
                            'product_attribute_id' => $attributeId,
                            'value' => $label,
                            'status' => true,
                        ]);
                    }
                    $optionIds[] = $option->id;
                }

                $options = ProductAttributeOption::query()->whereIn('id', $optionIds)->get()->keyBy('id');
                $syncPayload = [];
                foreach ($optionIds as $oid) {
                    $opt = $options->get($oid);
                    if ($opt) {
                        $syncPayload[$opt->id] = ['product_attribute_id' => $opt->product_attribute_id];
                    }
                }
                $result->attributeOptions()->sync($syncPayload);

                $variantPrices = (array) $request->input('variant_price', []);
                $variantStocks = (array) $request->input('variant_stock', []);
                foreach ($optionIds as $index => $optionId) {
                    $opt = $options->get($optionId);
                    if (!$opt) {
                        continue;
                    }
                    ProductVariant::query()->updateOrCreate(
                        ['product_id' => $result->id, 'product_attribute_option_id' => $optionId],
                        [
                            'price' => (float) ($variantPrices[$index] ?? $result->price ?? 0),
                            'stock' => max((int) ($variantStocks[$index] ?? 0), 0),
                            'max_purchase_qty' => null,
                            'status' => true,
                        ]
                    );
                }
                ProductVariant::query()
                    ->where('product_id', $result->id)
                    ->whereNotIn('product_attribute_option_id', $optionIds)
                    ->delete();

                $activeVariants = ProductVariant::query()
                    ->where('product_id', $result->id)
                    ->where('status', true)
                    ->get();
                if ($activeVariants->isNotEmpty()) {
                    $result->price = (float) $activeVariants->min('price');
                    $result->total_stock = (int) $activeVariants->sum('stock');
                    $result->save();
                }
            }
        } else {
            $result->attributeOptions()->sync([]);
            ProductVariant::query()->where('product_id', $result->id)->delete();
        }
        if ($request->hasFile('seo_image')) {
            storeMediaFile($result, $request->file('seo_image'), 'seo_image');
        }
        if ($request->hasFile('product_attachment')) {
            storeMediaFile($result, $request->file('product_attachment'), 'product_attachment');
        } elseif ($request->id && !getMediaFileExit($result, 'product_attachment')) {
            // keep existing
        } elseif (!$request->id && !getMediaFileExit($result, 'product_attachment')) {
            return redirect()->route('product.create', ['id' => $result->id])->withErrors(['product_attachment' => 'The attachment field is required.'])->withInput();
        }
        $message = $result->wasRecentlyCreated ? __('messages.save_form', ['form' => __('messages.product')]) : __('messages.update_form', ['form' => __('messages.product')]);
        return redirect(route('product.index'))->withSuccess($message);
    }

    public function show($id)
    {
        $locale = app()->getLocale();
        $product = Product::findOrFail($id);
        $globalSeoSetting = \App\Models\SeoSetting::first();
        $metaTitle = $product->translate('meta_title', $locale) ?? $product->meta_title ?? $product->name;
        $metaDescription = $product->translate('meta_description', $locale) ?? $product->meta_description ?? '';
        $metaKeywords = $product->translate('meta_keywords', $locale) ?? $product->meta_keywords ?? '';
        $slug = $product->slug ?? '';
        $seoImage = $product->getFirstMediaUrl('seo_image');
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.product')]);
        return view('product.view', compact('product', 'metaTitle', 'metaDescription', 'metaKeywords', 'slug', 'seoImage', 'pageTitle', 'globalSeoSetting'));
    }

    public function destroy($id)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $product = Product::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.product')]);
        if ($product) {
            $product->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.product')]);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    public function action(Request $request)
    {
        $product = Product::withTrashed()->find($request->id);
        $msg = __('messages.not_found_entry', ['name' => __('messages.product')]);
        if ($product) {
            if ($request->type === 'restore') {
                $product->restore();
                $msg = __('messages.msg_restored', ['name' => __('messages.product')]);
            }
            if ($request->type === 'forcedelete') {
                $product->forceDelete();
                $msg = __('messages.msg_forcedelete', ['name' => __('messages.product')]);
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
                Product::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_service_status_updated');
                break;
            case 'delete':
                Product::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_service_deleted');
                break;
            case 'restore':
                Product::whereIn('id', $ids)->restore();
                $message = __('messages.bulk_service_restored');
                break;
            case 'permanently-delete':
                Product::whereIn('id', $ids)->forceDelete();
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
        $product = Product::find($request->id);
        if ($product) {
            $product->service_request_status = ($request->status == 'approved') ? 'approve' : 'reject';
            $product->reject_reason = $request->reason;
            $product->save();
            $provider = User::find($product->provider_id);
            $activity_data = [
                'activity_type' => ($request->status == 'approved') ? 'service_request_approved' : 'service_request_reject',
                'service_id' => $product->id,
                'id' => $product->id,
                'provider_id' => $product->provider_id,
                'provider_name' => $provider->display_name ?? 'Unknown User',
                'user_name' => $provider?->display_name ?? 'Unknown User',
                'service_name' => $product->name,
                'reason' => $request->reason,
            ];
            $this->sendNotification($activity_data);

            return response()->json([
                'success' => true,
                'status' => $request->status,
                'serviceId' => $product->id,
                'providerId' => $product->provider_id,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Product not found']);
    }
}
