<?php

namespace App\Domains\Payments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost' => ['required', 'integer', 'min:0'],
            'comments' => ['nullable', 'string'],
        ];
    }
}
