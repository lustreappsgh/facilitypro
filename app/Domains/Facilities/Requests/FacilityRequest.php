<?php

namespace App\Domains\Facilities\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'facility_type_id' => ['required', 'exists:facility_types,id'],
            'parent_id' => ['nullable', 'exists:facilities,id'],
            'condition' => ['required', 'string'],
            'managed_by' => ['nullable', 'exists:users,id'],
        ];
    }
}
