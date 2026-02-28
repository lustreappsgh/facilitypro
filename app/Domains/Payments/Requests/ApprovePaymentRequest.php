<?php

namespace App\Domains\Payments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:500'],
        ];
    }
}
