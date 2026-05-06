<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;
        $providerId = request()->provider_id; // or however you get provider_id
        $isApi = request()->is('api/*');

        $rules = [
               'name' => [
            'required',
            Rule::unique('services', 'name')
                ->ignore($id) // ignore current service when updating
                ->where(function ($query) use ($providerId) {
                    return $query->where('provider_id', $providerId);
                }),
            ],
            'category_id'                    => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('module_type', 'service')->where('status', 1)),
            ],
            'type'                           => 'required',
            'price'                          => 'required|min:0',
            'status'                         => 'required',
            'service_attachment.*'           => 'image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max per file
        ];

        // Require at least one attachment for new services
        if (!$id) {
            if ($isApi){
                $rules['attachment_count'] = 'required|integer|min:1';
                $rules['service_attachment_0'] = 'required|file|image|mimes:jpeg,png,jpg,gif|max:10240';
                $rules['service_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['service_attachment'] = 'required|array|min:1';
                $rules['service_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        } else {
            if ($isApi) {
                $rules['service_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['service_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        }
        // Only apply SEO validation if SEO is enabled
        if (request()->has('seo_enabled') && request()->seo_enabled) {
            $rules['meta_title'] = 'required|string|max:255|unique:services,meta_title,'.$id;
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
        if ( request()->is('api*')){
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data,422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
