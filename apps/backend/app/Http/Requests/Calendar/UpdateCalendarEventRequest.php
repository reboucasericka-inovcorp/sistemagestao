<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'duration' => ['sometimes', 'integer', 'min:1'],
            'entity_id' => ['sometimes', 'integer', Rule::exists('entities', 'id')],
            'type_id' => ['nullable', 'integer', Rule::exists('calendar_types', 'id')],
            'action_id' => ['nullable', 'integer', Rule::exists('calendar_actions', 'id')],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type_id' => $this->normalizeNullableInteger($this->input('type_id')),
            'action_id' => $this->normalizeNullableInteger($this->input('action_id')),
        ]);
    }

    private function normalizeNullableInteger(mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }
}
