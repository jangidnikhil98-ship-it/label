<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:NEW,VERIFIED,ACCEPTED,REJECTED,SHIPPED,COMPLETED',
            'packing_status' => 'nullable|string|in:NOT_PACKED,READY,PACKED',
            'customer_name' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
        ];
    }
}
