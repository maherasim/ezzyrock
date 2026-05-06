<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = request()->id;
        $providerId = request()->provider_id;
        if (empty($providerId) && $id) {
            $providerId = \App\Models\Post::where('id', $id)->value('provider_id');
        }
        if (empty($providerId) && auth()->check()) {
            $providerId = auth()->id();
        }
        $isApi = request()->is('api/*');

        $rules = [
            'name' => [
                'required',
                Rule::unique('posts', 'name')
                    ->ignore($id)
                    ->where(function ($query) use ($providerId) {
                        return $query->where('provider_id', $providerId);
                    }),
            ],
            'category_id'            => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('module_type', 'classified')->where('status', 1)),
            ],
            'type'                   => 'required',
            'price'                  => 'required|min:0',
            'status'                 => 'required',
            'post_attachment.*'      => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ];

        if (!$id) {
            if ($isApi) {
                $rules['attachment_count'] = 'required|integer|min:1';
                $rules['post_attachment_0'] = 'required|file|image|mimes:jpeg,png,jpg,gif|max:10240';
                $rules['post_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['post_attachment'] = 'required|array|min:1';
                $rules['post_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        } else {
            if ($isApi) {
                $rules['post_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['post_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        }

        if (request()->has('seo_enabled') && request()->seo_enabled) {
            $rules['meta_title'] = 'required|string|max:255|unique:posts,meta_title,' . $id;
            $rules['meta_description'] = 'required|string|max:200';
            $rules['meta_keywords'] = 'required|string';
        }

        return $rules;
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
