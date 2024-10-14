<?php

namespace  App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'business_id' => 'required|exists:business,id',
            'short_code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'category_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|unique:categories,slug|max:255',
            // 'created_by' => 'required|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The category name is required.',
            'business_id.required' => 'The business ID is required.',
            'business_id.exists' => 'The selected business does not exist.',
            'parent_id.exists' => 'The selected parent category does not exist.',
            'slug.unique' => 'The slug must be unique.',
        ];
    }
}
