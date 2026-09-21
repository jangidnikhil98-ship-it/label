<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw_text' => 'nullable|string',
            'customer_name' => 'nullable|string|max:100',
            'message_file' => 'nullable|file|mimes:txt,png,jpg,jpeg,zip|max:51200',
        ];
    }
}
