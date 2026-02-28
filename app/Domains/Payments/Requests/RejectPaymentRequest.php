<?php

namespace App\Domains\Payments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
