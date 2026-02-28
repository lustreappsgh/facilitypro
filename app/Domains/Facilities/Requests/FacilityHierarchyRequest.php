<?php

namespace App\Domains\Facilities\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacilityHierarchyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:facilities,id'],
        ];
    }
}
