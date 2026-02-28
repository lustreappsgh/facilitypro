<?php

namespace App\Domains\Todos\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'required_without:facility_ids', 'exists:facilities,id'],
            'facility_ids' => ['nullable', 'required_without:facility_id', 'array'],
            'facility_ids.*' => ['exists:facilities,id'],
            'description' => ['required', 'string', 'max:1000'],
        ];
    }
}
