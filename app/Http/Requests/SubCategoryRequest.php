<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('id') && $this->input('id') !== '' && $this->input('id') !== null) {
            if (! $this->filled('module_type')) {
                $sub = \App\Models\SubCategory::with('category')->find($this->input('id'));
                if ($sub && $sub->category) {
                    $this->merge(['module_type' => $sub->category->module_type]);
                }
            }
        } else {
            $m = session('admin_subcategory_create_module');
            if (in_array($m, ['service', 'ecommerce', 'classified'], true)) {
                $this->merge(['module_type' => $m]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;

        $rules = [
            'module_type' => 'required|in:service,ecommerce,classified',
            'name' => [
                'required',
                Rule::unique('sub_categories', 'name')
                    ->where(fn ($q) => $q->where('category_id', (int) request('category_id')))
                    ->ignore($id),
            ],
            'status' => 'required',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('module_type', request('module_type'))),
            ],
        ];

        // Only apply SEO validation if SEO is enabled
        if (request()->has('seo_enabled') && request()->seo_enabled) {
            $rules['meta_title'] = 'required|string|max:255|unique:sub_categories,meta_title,'.$id;
            $rules['meta_description'] = 'required|string|max:200';
            $rules['meta_keywords'] = 'required|string';
        }

        return $rules;
    }
}
