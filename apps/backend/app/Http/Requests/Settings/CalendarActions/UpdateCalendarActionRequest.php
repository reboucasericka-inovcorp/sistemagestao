<?php

namespace App\Http\Requests\Settings\CalendarActions;

use App\Models\Settings\CalendarActionModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $calendarAction = $this->route('calendar_action');
        $id = $calendarAction instanceof CalendarActionModel ? $calendarAction->getKey() : $calendarAction;

        return [
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('calendar_actions', 'name')->ignore($id)],
            'calendar_type_id' => ['nullable', 'integer', Rule::exists('calendar_types', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
