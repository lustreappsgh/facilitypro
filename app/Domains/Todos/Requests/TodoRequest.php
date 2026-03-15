<?php

namespace App\Domains\Todos\Requests;

use Carbon\Carbon;
use Closure;
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
            'week' => ['nullable', 'date', $this->futureWeekRule()],
            'bulk_todos' => ['sometimes', 'array', 'min:1'],
            'bulk_todos.*.facility_id' => ['required', 'exists:facilities,id'],
            'bulk_todos.*.description' => ['required', 'string', 'max:1000'],
            'bulk_todos.*.week' => ['nullable', 'date', $this->futureWeekRule()],
        ];
    }

    protected function futureWeekRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $selectedWeekStart = Carbon::parse($value)->startOfWeek(Carbon::MONDAY);
            $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $nextWeekStart = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY);

            if ($selectedWeekStart->lt($currentWeekStart) || $selectedWeekStart->gt($nextWeekStart)) {
                $fail('Todos can only be created for the current week or next week.');
            }
        };
    }
}
