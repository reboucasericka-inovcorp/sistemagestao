<?php

namespace App\Http\Requests\Proposals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proposal_date' => ['sometimes', 'date'],
            'client_id' => ['sometimes', 'integer', Rule::exists('entities', 'id')],
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
