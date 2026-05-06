<?php

namespace App\Http\Controllers\API;

use App\Models\Shop;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShopRequest;
use App\Http\Resources\API\ShopResource;
use App\Http\Resources\API\ShopDetailResource;
use App\Models\Setting;
use Carbon\Carbon;

class ShopController extends Controller
{


    public function getShopList(Request $request)
    {

        $perPage = min($request->input('per_page', 10), 100);
        $page = $request->input('page', 1);

        $shops = Shop::where('is_active', 1)->with(['city', 'state', 'country', 'provider']);

        if ($request->filled('provider_id')) {
            $providerIds = explode(',', $request->provider_id);
            $shops->whereIn('provider_id', $providerIds);
        }

        if ($request->filled('service_id')) {
            $serviceIds = explode(',', $request->service_id);
            $shops->whereHas('services', function ($query) use ($serviceIds) {
                $query->whereIn('services.id', $serviceIds);
            });
        }

        if ($request->has('search') && !empty($request->search)) {

            $shops = $shops->where('shop_name', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('country_id') && !empty($request->country_id)) {

            $shops = $shops->where('country_id', $request->country_id);
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $shops->count();
            }
        }

        $shops = $shops->orderBy('created_at', 'desc')->paginate($perPage);

        $items = ShopResource::collection($shops);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],

            'data' => $items,
        ];

        return comman_custom_response($response);
    }

    public function getShopDetail($id)
    {
        $shop = Shop::withTrashed()->with(['city', 'state', 'country', 'provider'])->find($id);

        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Shop detail retrieved successfully.',
            'shop' => new ShopDetailResource($shop)
        ], 200);
    }

    public function shopCreate(ShopRequest $request)
    {
        $data = $request->except(['service_ids', 'shop_attachment']);

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode($sitesetup->value);
        $timezone = $sitesetupValue->time_zone ?? 'UTC';

            $data['shop_start_time'] = Carbon::parse($data['shop_start_time'], $timezone)
        ->setTimezone('UTC')
        ->format('H:i:s');

    $data['shop_end_time'] = Carbon::parse($data['shop_end_time'], $timezone)
        ->setTimezone('UTC')
        ->format('H:i:s');


        $shop = Shop::create($data);

        // Sync selected services
        if ($request->filled('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        }

        if ($request->is('api/*')) {
            // Handle API image uploads
            $file = [];
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachmentKey = "shop_attachment_" . $i;
                    if ($request->$attachmentKey != null) {
                        $file[] = $request->$attachmentKey;
                    }
                }

                if (!empty($file)) {
                    storeMediaFile($shop, $file, 'shop_attachment');
                }
            }
        } else {
            // Handle Web image upload
            if ($request->hasFile('shop_attachment')) {
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->route('shop.create')
                    ->withErrors(['shop_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Shop created successfully.',
        ], 201);
    }

    public function shopUpdate(ShopRequest $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $data = $request->except(['service_ids', 'shop_attachment']);

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode($sitesetup->value);
        $timezone = $sitesetupValue->time_zone ?? 'UTC';

            $data['shop_start_time'] = Carbon::parse($data['shop_start_time'], $timezone)
        ->setTimezone('UTC')
        ->format('H:i:s');

    $data['shop_end_time'] = Carbon::parse($data['shop_end_time'], $timezone)
        ->setTimezone('UTC')
        ->format('H:i:s');


        // Update shop
        $shop->update($data);

        // Sync selected services
        if ($request->filled('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        }

        if ($request->is('api/*')) {
            // Handle API image upload
            $file = [];
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachmentKey = "shop_attachment_" . $i;
                    if ($request->$attachmentKey != null) {
                        $file[] = $request->$attachmentKey;
                    }
                }

                if (!empty($file)) {
                    // Optional: clear old media before adding new
                    $shop->clearMediaCollection('shop_attachment');
                    storeMediaFile($shop, $file, 'shop_attachment');
                }
            }
        } else {
            // Handle web image upload
            if ($request->hasFile('shop_attachment')) {
                $shop->clearMediaCollection('shop_attachment'); // Remove old images
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->route('shop.edit', $shop->id)
                    ->withErrors(['shop_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Shop updated successfully.',
        ], 201);
    }

    public function deleteShop($id)
    {

        $shop = Shop::find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        $shop->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shop deleted successfully.',
        ], 201);
    }

    public function restoreShop($id)
    {
        $shop = Shop::onlyTrashed()->find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        $shop->restore();

        return response()->json([
            'status' => true,
            'message' => 'Shop restored successfully.',
        ], 201);
    }

    public function forceDeleteShop($id)
    {


        $shop = Shop::onlyTrashed()->find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        $shop->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'Shop deleted permanently.',
        ], 201);
    }
}
