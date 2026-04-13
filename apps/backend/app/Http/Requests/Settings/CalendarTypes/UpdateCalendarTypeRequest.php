<?php

namespace App\Http\Requests\Settings\CalendarTypes;

use App\Models\Settings\CalendarTypeModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $calendarType = $this->route('calendar_type');
        $id = $calendarType instanceof CalendarTypeModel ? $calendarType->getKey() : $calendarType;

        return [
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('calendar_types', 'name')->ignore($id)],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
