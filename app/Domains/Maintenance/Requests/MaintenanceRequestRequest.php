<?php

namespace App\Domains\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required_without_all:facility_ids,bulk_requests', 'nullable', 'exists:facilities,id'],
            'facility_ids' => ['required_without_all:facility_id,bulk_requests', 'array', 'min:1'],
            'facility_ids.*' => ['exists:facilities,id'],
            'bulk_requests' => ['sometimes', 'array', 'min:1'],
            'bulk_requests.*.facility_id' => ['required', 'exists:facilities,id'],
            'bulk_requests.*.request_type_id' => ['required', 'exists:request_types,id'],
            'bulk_requests.*.description' => ['nullable', 'string'],
            'bulk_requests.*.cost' => ['nullable', 'numeric'],
            'request_type_id' => ['required_without:bulk_requests', 'exists:request_types,id'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'], // Status changes should ideally go through dedicated actions
        ];
    }
}
