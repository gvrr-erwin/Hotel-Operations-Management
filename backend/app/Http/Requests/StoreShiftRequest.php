<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'    => ['required', 'exists:users,id'],
            'date'       => ['required', 'date_format:Y-m-d'],
            'shift_type' => ['required', Rule::in(['morning', 'afternoon', 'evening', 'night'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i'],
            'status'     => ['nullable', Rule::in(['scheduled', 'published', 'cancelled'])],
            'notes'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
