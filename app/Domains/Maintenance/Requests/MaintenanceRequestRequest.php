<?php

namespace App\Domains\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\MaintenanceStatus;

class MaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required_without:facility_ids', 'nullable', 'exists:facilities,id'],
            'facility_ids' => ['required_without:facility_id', 'array', 'min:1'],
            'facility_ids.*' => ['exists:facilities,id'],
            'request_type_id' => ['required', 'exists:request_types,id'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'], // Status changes should ideally go through dedicated actions
        ];
    }
}
