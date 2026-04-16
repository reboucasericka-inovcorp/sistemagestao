<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_date' => ['required', 'date'],
            'client_id' => ['required', 'integer', Rule::exists('entities', 'id')->where('is_client', true)],
            'status' => ['sometimes', Rule::in(['draft', 'closed'])],
            'items' => ['sometimes', 'array'],
            'items.*.article_id' => ['required_with:items', 'integer', Rule::exists('articles', 'id')],
            'items.*.supplier_id' => ['required_with:items', 'integer', Rule::exists('entities', 'id')],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.cost_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.total' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
