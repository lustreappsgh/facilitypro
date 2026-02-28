<?php

namespace App\Domains\Payments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'maintenance_request_id' => ['required', 'exists:maintenance_requests,id'],
            'cost' => ['required', 'integer'],
            'amount_payed' => ['required', 'integer'],
            'comments' => ['nullable', 'string'],
        ];
    }
}
