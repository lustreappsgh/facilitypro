<?php

namespace App\Domains\Inspections\Requests;

use App\Enums\Condition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'inspection_date' => ['required', 'date'],
            'facility_id' => ['required_without:facility_ids', 'nullable', 'exists:facilities,id'],
            'facility_ids' => ['required_without:facility_id', 'array', 'min:1'],
            'facility_ids.*' => ['exists:facilities,id'],
            'condition' => [
                'required',
                Rule::in(array_map(fn (Condition $condition) => $condition->name, Condition::cases())),
            ],
            'comments' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'maintenance_request_type_id' => ['nullable', 'exists:request_types,id'],
        ];
    }
}
