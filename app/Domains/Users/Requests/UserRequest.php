<?php

namespace App\Domains\Users\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;
        $roles = $this->input('roles', []);
        $isAdmin = in_array('Admin', $roles, true);
        $promotingToAdmin = $isAdmin && $user instanceof User && ! $user->can('users.manage');

        $passwordRules = [$userId ? 'nullable' : 'required', 'string', 'min:10'];
        if ($isAdmin) {
            $passwordRules = [
                $promotingToAdmin ? 'required' : ($userId ? 'nullable' : 'nullable'),
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ];
        }

        $nameRules = [$userId ? 'sometimes' : 'required', 'string', 'max:255'];
        $emailRules = [
            $userId ? 'sometimes' : 'required',
            'email',
            'max:255',
            Rule::unique(User::class)->ignore($userId),
        ];

        return [
            'name' => $nameRules,
            'email' => $emailRules,
            'password' => $passwordRules,
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['sometimes', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id')->where('is_active', true),
                Rule::notIn(array_filter([$userId])),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            $managerId = $this->input('manager_id');

            if (! $managerId) {
                return;
            }

            if ($user instanceof User && $user->subordinates()->exists()) {
                $validator->errors()->add(
                    'manager_id',
                    'Remove direct reports before assigning a supervising manager.'
                );
            }

            $manager = User::query()->active()->find($managerId);
            if (! $manager) {
                return;
            }

            if ($manager->hasAnyRole(['Admin', 'Manager']) || ! $manager->hasRole('Facility Manager')) {
                $validator->errors()->add(
                    'manager_id',
                    'Selected manager is not eligible to supervise facility managers.'
                );
            }

            if ($manager->manager_id) {
                $validator->errors()->add(
                    'manager_id',
                    'Selected manager already reports to another manager.'
                );
            }
        });
    }
}
