<?php

namespace App\Domains\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteMaintenanceRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'maintenance_request_ids' => ['required', 'array', 'min:1'],
            'maintenance_request_ids.*' => ['required', 'integer', 'distinct', 'exists:maintenance_requests,id'],
            'redirect_to' => ['nullable', 'string'],
        ];
    }
}
