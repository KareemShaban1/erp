<?php

namespace  App\Http\Requests\Warranty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarrantyRequest extends FormRequest
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
        return [
            'business_id' => 'required|exists:business,id',
            'actual_name' => 'required|string|max:255|unique:Warrantys,actual_name',
            'short_name' => 'required|string|max:50|unique:Warrantys,short_name',
            'allow_decimal' => 'required|boolean',
            'created_by' => 'required|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'business_id.required' => 'The business ID is required.',
            'business_id.exists' => 'The selected business does not exist.',
            'actual_name.required' => 'The actual name is required.',
            'actual_name.unique' => 'The actual name must be unique.',
            'short_name.required' => 'The short name is required.',
            'short_name.unique' => 'The short name must be unique.',
            'allow_decimal.required' => 'The allow decimal field is required.',
            'allow_decimal.boolean' => 'The allow decimal field must be true or false.',
            'created_by.required' => 'The creator\'s user ID is required.',
            'created_by.exists' => 'The specified user does not exist.',
        ];
    }
}
