<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rateId = $this->route('rate')?->id;

        return [
            'hotel_id'      => ['required', 'exists:hotels,id'],
            'room_type_id'  => ['required', 'exists:room_types,id'],
            'date'          => ['required', 'date_format:Y-m-d'],
            'rate'          => ['required', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }
}
