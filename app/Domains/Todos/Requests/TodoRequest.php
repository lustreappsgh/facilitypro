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
            'facility_id' => ['nullable', 'required_without_all:facility_ids,bulk_todos', 'exists:facilities,id'],
            'facility_ids' => ['nullable', 'required_without_all:facility_id,bulk_todos', 'array', 'min:1'],
            'facility_ids.*' => ['exists:facilities,id'],
            'description' => ['required_without:bulk_todos', 'string', 'max:1000'],
            'bulk_todos' => ['sometimes', 'array', 'min:1'],
            'bulk_todos.*.facility_id' => ['required', 'exists:facilities,id'],
            'bulk_todos.*.description' => ['required', 'string', 'max:1000'],
        ];
    }
}
