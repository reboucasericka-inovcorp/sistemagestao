<?php

namespace App\Http\Requests\Settings\CalendarActions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('calendar_actions', 'name')],
            'calendar_type_id' => ['required', 'integer', Rule::exists('calendar_types', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
