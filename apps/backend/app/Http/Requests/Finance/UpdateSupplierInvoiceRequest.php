<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['sometimes', 'string', 'max:255', Rule::unique('supplier_invoices', 'number')->ignore($this->route('id'))],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date', 'after_or_equal:invoice_date'],
            'supplier_id' => ['sometimes', 'integer', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'supplier_order_id' => ['nullable', 'integer', Rule::exists('supplier_orders', 'id')],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['pending_payment', 'paid'])],
            'send_payment_receipt_email' => ['sometimes', 'boolean'],
            'document_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip'],
            'payment_proof_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip'],
        ];
    }
}
