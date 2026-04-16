<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_date' => ['sometimes', 'date'],
            'supplier_id' => ['sometimes', 'integer', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'status' => ['sometimes', Rule::in(['draft', 'confirmed'])],
            'items' => ['sometimes', 'array'],
            'items.*.article_id' => ['required_with:items', 'integer', Rule::exists('articles', 'id')],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.cost_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.total' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
