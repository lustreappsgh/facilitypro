<?php

namespace App\Domains\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagerRequestTypePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type_permissions' => ['array'],
            'request_type_permissions.*.request_type_id' => ['required', 'integer', 'exists:request_types,id'],
            'request_type_permissions.*.can_approve' => ['boolean'],
            'request_type_permissions.*.can_reject' => ['boolean'],
        ];
    }
}
