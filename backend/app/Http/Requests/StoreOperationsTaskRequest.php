<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationsTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category'    => ['required', Rule::in(['housekeeping', 'maintenance', 'front_desk', 'food_beverage', 'other'])],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status'      => ['nullable', Rule::in(['open', 'in_progress', 'completed', 'cancelled'])],
            'room_number' => ['nullable', 'string', 'max:20'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_at'      => ['nullable', 'date'],
        ];
    }
}
