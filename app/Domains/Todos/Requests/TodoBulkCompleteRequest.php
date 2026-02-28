<?php

namespace App\Domains\Todos\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TodoBulkCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'todo_ids' => ['required', 'array', 'min:1'],
            'todo_ids.*' => ['integer', 'exists:todos,id'],
        ];
    }
}

