<?php

namespace App\Http\Controllers;

use App\Models\FreePostSetting;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FreePostSettingController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Free Post Settings';
        $auth_user = authSession();
        $assets = ['datatable'];
        $freePostSetting = FreePostSetting::find($request->id) ?? new FreePostSetting();

        return view('free_post_setting.index', compact('pageTitle', 'auth_user', 'assets', 'freePostSetting'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = FreePostSetting::query()->list();
        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $query->where('status', $filter['column_status']);
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $row->id . '" name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->editColumn('title', function ($row) {
                return '<a class="btn-link btn-link-hover" href="' . route('free-post-settings.index', ['id' => $row->id]) . '">' . e($row->title) . '</a>';
            })
            ->editColumn('free_posts', function ($row) {
                return (int) $row->free_posts;
            })
            ->editColumn('status', function ($row) {
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="free_post_setting_status" ' . ($row->status ? 'checked' : '') . ' value="' . $row->id . '" id="free_post_setting_' . $row->id . '" data-id="' . $row->id . '">
                        <label class="custom-control-label" for="free_post_setting_' . $row->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($freePostSetting) {
                return view('free_post_setting.action', compact('freePostSetting'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['check', 'title', 'status', 'action'])
            ->toJson();
    }

    public function store(Request $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:free_post_settings,id',
            'title' => 'required|string|max:255',
            'free_posts' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
            'description' => 'nullable|string',
        ]);

        $result = FreePostSetting::updateOrCreate(
            ['id' => $validated['id'] ?? null],
            [
                'title' => $validated['title'],
                'free_posts' => $validated['free_posts'],
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]
        );

        $message = $result->wasRecentlyCreated ? 'Free post setting saved successfully.' : 'Free post setting updated successfully.';

        return redirect()->route('free-post-settings.index')->withSuccess($message);
    }

    public function bulk_action(Request $request)
    {
        $ids = array_filter(explode(',', $request->rowIds));
        $actionType = $request->action_type;

        switch ($actionType) {
            case 'change-status':
                FreePostSetting::whereIn('id', $ids)->update(['status' => $request->status]);
                return response()->json(['status' => true, 'message' => 'Bulk Free Post Settings Status Updated']);

            case 'delete':
                FreePostSetting::whereIn('id', $ids)->delete();
                return response()->json(['status' => true, 'message' => 'Bulk Free Post Settings Deleted']);

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
        }
    }

    public function destroy($id)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        FreePostSetting::where('id', $id)->delete();

        return comman_custom_response(['message' => 'Free post setting deleted successfully.', 'status' => true]);
    }
}
