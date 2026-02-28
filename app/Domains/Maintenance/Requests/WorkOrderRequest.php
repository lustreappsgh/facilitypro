<?php

namespace App\Domains\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'maintenance_request_id' => [
                Rule::requiredIf($this->isMethod('post')),
                'exists:maintenance_requests,id',
            ],
            'vendor_id' => [
                Rule::requiredIf($this->isMethod('post')),
                'exists:vendors,id',
            ],
            'scheduled_date' => ['nullable', 'date'],
            'estimated_cost' => ['nullable', 'integer'],
            'actual_cost' => ['nullable', 'integer'],
            'status' => [
                'nullable',
                'string',
                Rule::in(['assigned', 'in_progress', 'completed', 'cancelled']),
            ],
        ];
    }
}
