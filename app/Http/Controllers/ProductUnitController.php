<?php

namespace App\Http\Controllers;

use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    public function index()
    {
        $units = ProductUnit::query()
            ->orderBy('name')
            ->get();

        $auth_user = authSession();

        return view('product-unit.index', compact('units', 'auth_user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120|unique:product_units,name',
            'status' => 'nullable|boolean',
        ]);

        ProductUnit::query()->create([
            'name' => trim((string) $request->name),
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', __('messages.product_unit_created'));
    }

    public function update(Request $request, ProductUnit $productUnit)
    {
        $request->validate([
            'name' => 'required|string|max:120|unique:product_units,name,' . $productUnit->id,
            'status' => 'nullable|boolean',
        ]);

        $productUnit->update([
            'name' => trim((string) $request->name),
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', __('messages.product_unit_updated'));
    }

    public function destroy(ProductUnit $productUnit)
    {
        $productUnit->delete();

        return back()->with('success', __('messages.product_unit_deleted'));
    }
}
