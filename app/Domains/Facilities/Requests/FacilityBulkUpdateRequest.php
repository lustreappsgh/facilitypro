<?php

namespace App\Domains\Facilities\Requests;

use App\Enums\Condition;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityBulkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $conditionValues = array_map(static fn (Condition $condition) => $condition->name, Condition::cases());

        return [
            'facility_ids' => ['required', 'array', 'min:1'],
            'facility_ids.*' => ['required', 'integer', 'distinct', 'exists:facilities,id'],
            'condition' => ['nullable', Rule::in($conditionValues)],
            'facility_type_id' => ['nullable', 'exists:facility_types,id'],
            'parent_id' => ['nullable', 'exists:facilities,id'],
            'managed_by' => ['nullable', Rule::exists(User::class, 'id')->where('is_active', true)],
        ];
    }
}

