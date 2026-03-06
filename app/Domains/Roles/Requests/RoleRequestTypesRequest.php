<?php

namespace App\Domains\Roles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequestTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type_ids' => ['array'],
            'request_type_ids.*' => ['integer', 'exists:request_types,id'],
        ];
    }
}
