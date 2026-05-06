<?php

namespace App\Http\Controllers;


use App\Models\City;
use App\Models\Shop;
use App\Models\User;
use App\Models\State;
use App\Models\Country;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Requests\ShopRequest;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Setting;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = [
            'column_status' => $request->get('column_status', ''),
        ];

        if ($request->provider_id) {
            $id = $request->provider_id;
            $type = "provider-shop";
        } else {
            $id = null;
            $type = null;
        }

        return view('shop.index', compact('filter', 'id', 'type'));
    }

    public function index_data(Request $request)
    {
        $query = Shop::with(['country', 'state', 'city', 'provider'])
            ->withTrashed()
            ->when(auth()->user()->hasRole('provider'), function ($q) {
                $q->where('shops.provider_id', auth()->id());
            });

        if (auth()->user()->hasRole('provider')) {
            $query->where('shops.provider_id', auth()->id());
        }

        if ($request->type === 'provider-shop') {
            $query->where('shops.provider_id', $request->id);
        }

        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $query->where('is_active', $filter['column_status']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('shop_name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%")
                            ->orWhereHas('city', function ($q2) use ($search) {
                                $q2->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('provider', function ($q3) use ($search) {
                                $q3->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="shop" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('shop_name', function ($shop) {
                $shopHtml = '
                    <div class="d-flex gap-3 align-items-center">
                        <img src="' . getSingleMedia($shop, 'shop_attachment', null) . '" alt="service" class="avatar avatar-40 rounded-pill">
                        <div class="text-start">
                            <h6 class="m-0">' . e($shop->shop_name) . '</h6>
                            <span>' . e($shop->email ?? '--') . '</span>
                        </div>
                    </div>
                ';

                if (is_null($shop->deleted_at)) {
                    return '<a href="' . route('shop.show', $shop->id) . '">' . $shopHtml . '</a>';
                } else {
                    return $shopHtml;
                }
            })
            ->orderColumn('shop_name', function ($query, $order) {
                $query->orderBy('shop_name', $order);
            })
            ->editColumn('provider_id', function ($shop) {
                return '<a href="' . route('provider_info', $shop->provider->id) . '">
                    <div class="d-flex gap-3 align-items-center">
                        <img src="' . getSingleMedia($shop->provider, 'profile_image', null) . '" alt="avatar" class="avatar avatar-40 rounded-pill">
                        <div class="text-start">
                            <h6 class="m-0">' . e($shop->provider->first_name) . ' ' . e($shop->provider->last_name) . '</h6>
                            <span>' . e($shop->provider->email ?? '--') . '</span>
                        </div>
                    </div>
                </a>';
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->leftJoin('users as providers', 'shops.provider_id', '=', 'providers.id')
                    ->whereColumn('shops.provider_id', 'providers.id')
                    ->orderBy('providers.first_name', $order)
                    ->orderBy('providers.last_name', $order)
                    ->select('shops.*');
            })
            ->editColumn('city_id', function ($shop) {
                return $shop->city?->name ?? '';
            })
            ->orderColumn('city_id', function ($query, $order) {
                $query->leftJoin('cities as c', 'shops.city_id', '=', 'c.id')
                    ->orderBy('c.name', $order)
                    ->select('shops.*');
            })
            ->editColumn('contact_number', function ($shop) {
                return e($shop->contact_number);
            })
            ->editColumn('is_active', function ($shop) {
                $disabled = $shop->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="shop_status" ' . ($shop->is_active == 1 ? "checked" : "") . ' ' . $disabled . ' value="' . $shop->id . '" id="' . $shop->id . '" data-id="' . $shop->id . '">
                        <label class="custom-control-label" for="' . $shop->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($shop) {
                return view('shop.action', compact('shop'))->render();
            })
            ->rawColumns(['check', 'shop_name', 'provider_id', 'city_id', 'contact_number', 'is_active', 'action'])
            ->toJson();
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                Shop::whereIn('id', $ids)->update(['is_active' => $request->status]);
                $message = 'Bulk Shop Status Updated';
                break;

            case 'delete':
                Shop::whereIn('id', $ids)->delete();
                $message = 'Bulk Shop Deleted';
                break;

            case 'restore':
                Shop::whereIn('id', $ids)->restore();
                $message = 'Bulk Shop Restored';
                break;

            case 'permanently-delete':
                Shop::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Shop Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (auth()->user()->hasRole('provider')) {
            $shop = Shop::with(['country', 'state', 'city', 'provider', 'services'])->findOrFail($id);
            if ($shop->provider_id !== auth()->id()) {
                return redirect()->route('shop.index')->with('success', 'Shop Not found.');
            }
        }

        $shop = Shop::with(['country', 'state', 'city', 'provider', 'services'])->findOrFail($id);
        return view('shop.show', compact('shop'));
    }

    public function create()
    {
        $url = route('shop.store');
        $countries = Country::select('id', 'name')->get();
        return view('shop.form', compact('url', 'countries'));
    }
    public function edit($id)
    {
        if (auth()->user()->hasRole('provider')) {
            $shop = Shop::with(['country', 'state', 'city', 'provider', 'services'])->findOrFail($id);
            if ($shop->provider_id !== auth()->id()) {
                return redirect()->route('shop.index')->with('success', 'Shop Not found.');
            }
        }

        $url = route('shop.update', $id);
        $countries = Country::select('id', 'name')->get();
        $shop = Shop::findOrFail($id);
        // Format start/end time as per site settings timezone & format
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode(optional($sitesetup)->value ?? '{}');
        $targetTimezone = isset($sitesetupValue->time_zone) ? trim((string) $sitesetupValue->time_zone) : 'UTC';
        $timeFormat = $sitesetupValue->time_format ?? 'H:i';

        $shop['shop_start_time'] = $shop->getRawOriginal('shop_start_time')
            ? Carbon::parse($shop->getRawOriginal('shop_start_time'), 'UTC')->setTimezone($targetTimezone)->format($timeFormat)
            : null;
        $shop['shop_end_time'] = $shop->getRawOriginal('shop_end_time')
            ? Carbon::parse($shop->getRawOriginal('shop_end_time'), 'UTC')->setTimezone($targetTimezone)->format($timeFormat)
            : null;

        return view('shop.form', compact('url', 'countries', 'shop'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShopRequest $request)
    {
        $data = $request->except(['service_ids', 'shop_attachment']);

   $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $sitesetupValue = json_decode($sitesetup->value);
    $timezone = $sitesetupValue->time_zone ?? 'UTC';

        $data['shop_start_time'] = Carbon::parse($request->shop_start_time, $timezone)
    ->setTimezone('UTC')
    ->format('H:i:s');

$data['shop_end_time'] = Carbon::parse($request->shop_end_time, $timezone)
    ->setTimezone('UTC')
    ->format('H:i:s');

        $shop = Shop::create($data);
        if ($request->has('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        }
        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "shop_attachment_" . $i;
                    if ($request->$attachment != null) {
                        $file[] = $request->$attachment;
                    }
                }
                storeMediaFile($shop, $file, 'shop_attachment');
            }
        } else {
            if ($request->hasFile('shop_attachment')) {
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->route('shop.create')
                    ->withErrors(['shop_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }
        return redirect()->route('shop.index')->with('success', 'Shop created successfully.');
    }
    public function update(ShopRequest $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $data = $request->except(['service_ids', 'shop_attachment']);

           $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $sitesetupValue = json_decode($sitesetup->value);
    $timezone = $sitesetupValue->time_zone ?? 'UTC';

       $data['shop_start_time'] = Carbon::parse($request->shop_start_time, $timezone)
    ->setTimezone('UTC')
    ->format('H:i:s');

$data['shop_end_time'] = Carbon::parse($request->shop_end_time, $timezone)
    ->setTimezone('UTC')
    ->format('H:i:s');

        $shop->update($data);

        if ($request->filled('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        } else {
            $shop->services()->sync([]);
        }

        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                $files = [];
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachmentKey = 'shop_attachment_' . $i;
                    if (!empty($request->$attachmentKey)) {
                        $files[] = $request->$attachmentKey;
                    }
                }
                if (!empty($files)) {
                    storeMediaFile($shop, $files, 'shop_attachment');
                }
            }
        } else {
            if ($request->hasFile('shop_attachment')) {
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->back()
                    ->withErrors(['shop_attachment' => 'The shop image is required.'])
                    ->withInput();
            }
        }

        return redirect()->route('shop.index')
            ->with('success', 'Shop updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     **/
    public function delete($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();
        $msg = 'Shop deleted successfully.';
        return response()->json(['message' => $msg, 'status' => true]);
    }
    public function restore($id)
    {
        $shop = Shop::withTrashed()->findOrFail($id);
        $shop->restore();
        $msg = 'Shop restored successfully.';
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $shop = Shop::withTrashed()->findOrFail($id);
        $shop->forceDelete();
        $msg = 'Shop deleted permanently.';
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Get states for a specific country.
     */

    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)->pluck('name', 'id');
        return response()->json($states);
    }


    /**
     * Get cities for a specific state.
     */
    public function getCities($stateId)
    {
        $cities = City::where('state_id', $stateId)->pluck('name', 'id');
        return response()->json($cities);
    }

    /**
     * Get all providers with their shops.
     */
   public function getProviders()
    {
        $providers = User::where('user_type','provider')->where('status', 1)->get();

        return response()->json($providers);
    }

    /**
     * Get all services for a specific provider.
     */
    public function getServices($providerId)
    {
        $services = Service::where('provider_id', $providerId)->select('id', 'name')->where('status',1)->where('service_request_status','approve')->get();
        return response()->json($services);
    }

    public function checkRegistration(Request $request)
    {
        $field = $request->field;
        $value = $request->value;

        if (!in_array($field, ['contact_number', 'email', 'registration_number'])) {
            return response()->json(['status' => false, 'message' => 'Invalid field']);
        }

        $exists = \App\Models\Shop::where($field, $value)->exists();

        return response()->json([
            'status' => !$exists,
            'message' => $exists ? ucfirst(str_replace('_', ' ', $field)) . ' already exists' : ''
        ]);
    }
}
