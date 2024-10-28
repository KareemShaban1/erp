<?php

namespace  App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:brands,name',
            'description' => 'nullable|string',
            'created_by' => 'required|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'business_id.required' => 'The business ID is required.',
            'business_id.exists' => 'The selected business does not exist.',
            'name.required' => 'The brand name is required.',
            'name.unique' => 'The brand name must be unique.',
            'created_by.required' => 'The user who created this brand must be specified.',
            'created_by.exists' => 'The specified user does not exist.',
        ];
    }
}
