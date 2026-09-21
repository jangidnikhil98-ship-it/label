<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'labels' => 'required|array',
            'labels.*' => 'required|file|mimes:pdf,zip,jpg,jpeg,png|max:20480',
        ];
    }
}
