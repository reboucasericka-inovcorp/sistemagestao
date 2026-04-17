<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:255', 'unique:supplier_invoices,number'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'supplier_id' => ['required', 'integer', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'supplier_order_id' => ['nullable', 'integer', Rule::exists('supplier_orders', 'id')],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending_payment', 'paid'])],
            'send_payment_receipt_email' => ['sometimes', 'boolean'],
            'document_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip'],
            'payment_proof_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip'],
        ];
    }
}
