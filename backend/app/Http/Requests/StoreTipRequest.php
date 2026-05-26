<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'date'        => ['required', 'date_format:Y-m-d'],
            'note'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
