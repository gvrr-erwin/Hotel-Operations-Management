<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimeLogRequest extends FormRequest
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
            'clock_in'   => ['required', 'date_format:H:i'],
            'clock_out'  => ['nullable', 'date_format:H:i'],
            'shift_type' => ['required', Rule::in(['morning', 'afternoon', 'evening', 'night'])],
            'status'     => ['nullable', Rule::in(['pending', 'approved', 'flagged'])],
            'notes'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
