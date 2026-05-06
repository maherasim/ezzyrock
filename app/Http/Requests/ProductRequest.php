<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = request()->id;
        $providerId = request()->provider_id;
        $isApi = request()->is('api/*');

        $rules = [
            'name' => [
                'required',
                Rule::unique('products', 'name')
                    ->ignore($id)
                    ->where(function ($query) use ($providerId) {
                        return $query->where('provider_id', $providerId);
                    }),
            ],
            'category_id'             => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('module_type', 'ecommerce')->where('status', 1)),
            ],
            'type'                    => 'required',
            'price'                   => 'required|min:0',
            'total_stock'             => 'required|integer|min:0',
            'max_purchase_qty'        => 'nullable|integer|min:1',
            'status'                  => 'required',
            'product_attachment.*'    => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'product_unit_id'         => 'nullable|integer|exists:product_units,id',
            'product_attribute_id'    => 'nullable|integer|exists:product_attributes,id',
            'variant_labels'          => 'nullable|array',
            'variant_labels.*'        => 'nullable|string|max:255',
            'variant_price'           => 'nullable|array',
            'variant_price.*'         => 'nullable|numeric|min:0',
            'variant_stock'           => 'nullable|array',
            'variant_stock.*'         => 'nullable|integer|min:0',
        ];

        if (!$id) {
            if ($isApi) {
                $rules['attachment_count'] = 'required|integer|min:1';
                $rules['product_attachment_0'] = 'required|file|image|mimes:jpeg,png,jpg,gif|max:10240';
                $rules['product_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['product_attachment'] = 'required|array|min:1';
                $rules['product_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        } else {
            if ($isApi) {
                $rules['product_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['product_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        }

        if (request()->has('seo_enabled') && request()->seo_enabled) {
            $rules['meta_title'] = 'required|string|max:255|unique:products,meta_title,' . $id;
            $rules['meta_description'] = 'required|string|max:200';
            $rules['meta_keywords'] = 'required|string';
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $labels = array_values(array_filter(array_map('trim', (array) $this->input('variant_labels', []))));
            if (count($labels) === 0) {
                return;
            }
            if (!$this->filled('product_attribute_id')) {
                $v->errors()->add('product_attribute_id', __('messages.product_variant_attribute_required'));
            }
            $prices = (array) $this->input('variant_price', []);
            $stocks = (array) $this->input('variant_stock', []);
            $n = count($labels);
            if (count($prices) !== $n || count($stocks) !== $n) {
                $v->errors()->add('variant_labels', __('messages.product_variant_rows_mismatch'));
            }
        });
    }

    public function messages()
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        if (request()->is('api*')) {
            $data = [
                'status'  => 'false',
                'message' => $validator->errors()->first(),
                'all_message' => $validator->errors()
            ];
            throw new HttpResponseException(response()->json($data, 422));
        }
        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
