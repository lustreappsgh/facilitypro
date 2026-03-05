<?php

namespace App\Domains\Facilities\Requests;

use App\Enums\Condition;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $conditionValues = array_map(static fn (Condition $condition) => $condition->name, Condition::cases());

        return [
            'name' => ['required_without:facilities', 'string', 'max:255'],
            'facility_type_id' => ['required_without:facilities', 'exists:facility_types,id'],
            'parent_id' => ['nullable', 'exists:facilities,id'],
            'condition' => ['required_without:facilities', Rule::in($conditionValues)],
            'managed_by' => ['nullable', Rule::exists(User::class, 'id')->where('is_active', true)],

            'facilities' => ['nullable', 'array', 'min:1'],
            'facilities.*.name' => ['required_with:facilities', 'string', 'max:255'],
            'facilities.*.facility_type_id' => ['required_with:facilities', 'exists:facility_types,id'],
            'facilities.*.parent_id' => ['nullable', 'exists:facilities,id'],
            'facilities.*.condition' => ['required_with:facilities', Rule::in($conditionValues)],
            'facilities.*.managed_by' => ['nullable', Rule::exists(User::class, 'id')->where('is_active', true)],
        ];
    }
}
