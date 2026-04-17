<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'invoice_date' => optional($this->invoice_date)->toDateString(),
            'due_date' => optional($this->due_date)->toDateString(),
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'supplier' => $this->supplier
                ? [
                    'id' => $this->supplier->id,
                    'name' => $this->supplier->name,
                    'number' => $this->supplier->number,
                    'email' => $this->supplier->email ?? null,
                ]
                : null,
            'supplier_order' => $this->supplierOrder
                ? [
                    'id' => $this->supplierOrder->id,
                    'number' => $this->supplierOrder->number,
                ]
                : null,
            'files' => $this->relationLoaded('files')
                ? $this->files->map(static fn ($file): array => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'category' => $file->category,
                    'download_url' => url(sprintf('/api/v1/digital-files/%d/download', $file->id)),
                    'created_at' => $file->created_at,
                ])->values()->all()
                : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
