<?php

namespace App\Domains\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserBulkStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
