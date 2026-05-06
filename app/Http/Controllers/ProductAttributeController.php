<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index()
    {
        $attributes = ProductAttribute::query()
            ->orderBy('id')
            ->get();

        $auth_user = authSession();

        return view('product-attribute.index', compact('attributes', 'auth_user'));
    }

    public function storeAttribute(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120|unique:product_attributes,name',
            'status' => 'nullable|boolean',
        ]);

        ProductAttribute::query()->create([
            'name' => trim((string) $request->name),
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', __('messages.attribute_created'));
    }

    public function updateAttribute(Request $request, ProductAttribute $productAttribute)
    {
        $request->validate([
            'name' => 'required|string|max:120|unique:product_attributes,name,' . $productAttribute->id,
            'status' => 'nullable|boolean',
        ]);

        $productAttribute->update([
            'name' => trim((string) $request->name),
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', __('messages.attribute_updated'));
    }

    public function deleteAttribute(ProductAttribute $productAttribute)
    {
        $productAttribute->delete();

        return back()->with('success', __('messages.attribute_deleted'));
    }

    public function storeOption(Request $request)
    {
        $request->validate([
            'product_attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:120',
            'status' => 'nullable|boolean',
        ]);

        ProductAttributeOption::query()->create([
            'product_attribute_id' => (int) $request->product_attribute_id,
            'value' => trim((string) $request->value),
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', 'Option created.');
    }

    public function updateOption(Request $request, ProductAttributeOption $productAttributeOption)
    {
        $request->validate([
            'value' => 'required|string|max:120',
            'status' => 'nullable|boolean',
        ]);

        $productAttributeOption->update([
            'value' => trim((string) $request->value),
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', 'Option updated.');
    }

    public function deleteOption(ProductAttributeOption $productAttributeOption)
    {
        $productAttributeOption->delete();

        return back()->with('success', 'Option deleted.');
    }
}

