<?php

namespace App\Domains\Facilities\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacilityBulkAssignManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_ids' => ['exclude_if:select_all_filtered,true', 'required', 'array', 'min:1'],
            'facility_ids.*' => ['exclude_if:select_all_filtered,true', 'required', 'integer', 'distinct', 'exists:facilities,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'select_all_filtered' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string'],
            'current_manager_id' => ['nullable', 'string'],
        ];
    }
}
