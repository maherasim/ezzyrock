<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class ShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        $shopId = $this->route('shop') ?? $this->route('id'); // Adjust to match your route

        return [
            'provider_id' => 'required|exists:users,id',
            'shop_name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'registration_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shops', 'registration_number')->ignore($shopId),
            ],
            'shop_start_time' => 'required|date_format:H:i',
            'shop_end_time' => 'required|date_format:H:i|after:shop_start_time',
            'contact_number' => 'required|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('shops', 'email')->ignore($shopId),
            ],
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'shop_attachment.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
