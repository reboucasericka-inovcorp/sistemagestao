<?php

namespace App\Http\Requests\DigitalArchive;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDigitalFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'fileable_id' => ['nullable', 'integer'],
            'fileable_type' => ['nullable', 'string', 'max:255'],
        ];
    }
}
