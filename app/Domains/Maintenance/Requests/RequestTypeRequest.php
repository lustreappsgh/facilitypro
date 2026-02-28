<?php

namespace App\Domains\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requestType = $this->route('requestType');
        $requestTypeId = $requestType?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('request_types', 'name')->ignore($requestTypeId),
            ],
        ];
    }
}
