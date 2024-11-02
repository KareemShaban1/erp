<?php

namespace  App\Http\Requests\OrderCancellation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderCancellationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_id' => 'required|exists:orders,id', // Ensure order exists
            'client_id' => 'required|exists:clients,id', // Ensure client exists
            'status' => 'required|in:requested,approved,rejected', // Must be one of the predefined statuses
            'reason' => 'required_if:status,requested|max:1000', // Reason is required when requesting cancellation
            'admin_response' => 'nullable|max:1000', // Optional admin response with a max length
            'requested_at' => 'nullable|date', // Optional date, should be a valid date format
            'processed_at' => 'nullable|date|after_or_equal:requested_at', // Should be a date after or equal to requested_at
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'order_id.required' => 'The order ID is required.',
            'order_id.exists' => 'The selected order does not exist.',
            'client_id.required' => 'The client ID is required.',
            'client_id.exists' => 'The selected client does not exist.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either requested, approved, or rejected.',
            'reason.required_if' => 'A reason is required for a cancellation request.',
            'reason.max' => 'The reason may not be greater than 1000 characters.',
            'admin_response.max' => 'The admin response may not be greater than 1000 characters.',
            'requested_at.date' => 'The requested date must be a valid date.',
            'processed_at.date' => 'The processed date must be a valid date.',
            'processed_at.after_or_equal' => 'The processed date must be after or equal to the requested date.',
        ];
    }
}
