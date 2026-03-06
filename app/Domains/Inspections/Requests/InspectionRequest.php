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
            'inspection_date' => ['required_without:bulk_inspections', 'date'],
            'facility_id' => ['required_without_all:facility_ids,bulk_inspections', 'nullable', 'exists:facilities,id'],
            'facility_ids' => ['required_without_all:facility_id,bulk_inspections', 'array', 'min:1'],
            'facility_ids.*' => ['exists:facilities,id'],
            'condition' => [
                'required_without:bulk_inspections',
                Rule::in(array_map(fn (Condition $condition) => $condition->name, Condition::cases())),
            ],
            'comments' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'maintenance_request_type_id' => ['nullable', 'exists:request_types,id'],
            'bulk_inspections' => ['sometimes', 'array', 'min:1'],
            'bulk_inspections.*.facility_id' => ['required', 'exists:facilities,id'],
            'bulk_inspections.*.inspection_date' => ['required', 'date'],
            'bulk_inspections.*.condition' => [
                'required',
                Rule::in(array_map(fn (Condition $condition) => $condition->name, Condition::cases())),
            ],
            'bulk_inspections.*.comments' => ['nullable', 'string'],
            'bulk_inspections.*.maintenance_request_type_id' => ['nullable', 'exists:request_types,id'],
        ];
    }
}
