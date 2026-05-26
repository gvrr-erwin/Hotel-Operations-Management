<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email'    => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:6'],
            'role'      => ['required', Rule::in(['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'])],
            'department'=> ['nullable', Rule::in(['front_desk', 'housekeeping', 'maintenance', 'food_beverage', 'management', 'other'])],
            'position'  => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
