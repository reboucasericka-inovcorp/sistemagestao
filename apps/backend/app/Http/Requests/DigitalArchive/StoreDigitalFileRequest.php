<?php

namespace App\Http\Requests\DigitalArchive;

use Illuminate\Foundation\Http\FormRequest;

class StoreDigitalFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip',
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'fileable_id' => ['nullable', 'integer'],
            'fileable_type' => ['nullable', 'string', 'max:255'],
        ];
    }
}
