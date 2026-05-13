<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPlanRequest extends FormRequest
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
        $rules = [
            'title' => 'required|max:255|unique:user_plan,title',
            'type' => 'required',
            'amount' => 'required|numeric|min:0',
            'status' => 'required',
            'duration' => 'required',
            'free_posts' => 'nullable|integer|min:0',
        ];

        if ($this->has('id') && !empty($this->id)) {
            $rules['title'] = 'required|max:255|unique:user_plan,title,' . $this->id;
        }

        return $rules;
    }
}
