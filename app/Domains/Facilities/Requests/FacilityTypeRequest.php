<?php

namespace App\Domains\Facilities\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $facilityType = $this->route('facilityType');
        $facilityTypeId = $facilityType?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('facility_types', 'name')->ignore($facilityTypeId),
            ],
        ];
    }
}
