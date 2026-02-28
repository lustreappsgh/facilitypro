<?php

namespace App\Domains\Users\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManagerReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $manager = $this->route('user');
        $managerId = $manager instanceof User ? $manager->id : null;

        return [
            'report_ids' => ['nullable', 'array'],
            'report_ids.*' => [
                'integer',
                Rule::exists(User::class, 'id'),
                Rule::notIn(array_filter([$managerId])),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $manager = $this->route('user');
            if (! $manager instanceof User) {
                return;
            }

            $reportIds = $this->input('report_ids', []);
            if ($manager->manager_id && ! empty($reportIds)) {
                $validator->errors()->add(
                    'report_ids',
                    'Remove the supervising manager before assigning direct reports.'
                );
            }
        });
    }
}
