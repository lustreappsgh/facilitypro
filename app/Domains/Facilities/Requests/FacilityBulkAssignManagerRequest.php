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
            'facility_ids' => ['required', 'array', 'min:1'],
            'facility_ids.*' => ['required', 'integer', 'distinct', 'exists:facilities,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}

