<?php

namespace App\Http\Requests\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'client_id' => ['sometimes', 'integer', Rule::exists('entities', 'id')->where('is_client', true)],
            'status' => ['sometimes', Rule::in(['draft', 'in_progress', 'completed'])],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'integer', Rule::exists('articles', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
        ];
    }
}
