<?php

namespace App\Services\Finance;

use App\Mail\Finance\SupplierInvoicePaymentReceiptMail;
use App\Models\DigitalFileModel;
use App\Models\EntityModel;
use App\Models\Finance\SupplierInvoiceModel;
use App\Services\DigitalArchive\DigitalFileService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SupplierInvoiceService
{
    public function __construct(
        private DigitalFileService $digitalFileService
    ) {
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $status = trim((string) Arr::get($filters, 'status', ''));
        $supplierId = (int) Arr::get($filters, 'supplier_id', 0);
        $supplierOrderId = (int) Arr::get($filters, 'supplier_order_id', 0);
        $dateFrom = trim((string) Arr::get($filters, 'date_from', ''));
        $dateTo = trim((string) Arr::get($filters, 'date_to', ''));
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'number', 'invoice_date', 'due_date', 'status', 'total_amount', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return SupplierInvoiceModel::query()
            ->with([
                'supplier:id,name,number',
                'supplierOrder:id,number',
                'files:id,name,file_path,mime_type,category,fileable_id,fileable_type',
            ])
            ->when($status !== '', static fn (Builder $query): Builder => $query->where('status', $status))
            ->when($supplierId > 0, static fn (Builder $query): Builder => $query->where('supplier_id', $supplierId))
            ->when($supplierOrderId > 0, static fn (Builder $query): Builder => $query->where('supplier_order_id', $supplierOrderId))
            ->when($dateFrom !== '', static fn (Builder $query): Builder => $query->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', static fn (Builder $query): Builder => $query->whereDate('invoice_date', '<=', $dateTo))
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $nested) use ($term): void {
                    $nested->where('number', 'like', $term)
                        ->orWhereHas('supplier', static function (Builder $supplierQuery) use ($term): void {
                            $supplierQuery->where('name', 'like', $term);
                        })
                        ->orWhereHas('supplierOrder', static function (Builder $orderQuery) use ($term): void {
                            $orderQuery->where('number', 'like', $term);
                        });
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function getById(int $id): SupplierInvoiceModel
    {
        return SupplierInvoiceModel::query()
            ->with([
                'supplier:id,name,number,email',
                'supplierOrder:id,number',
                'files:id,name,file_path,mime_type,category,fileable_id,fileable_type',
            ])
            ->findOrFail($id);
    }

    public function create(array $data, array $files, int $userId): SupplierInvoiceModel
    {
        return DB::transaction(function () use ($data, $files, $userId): SupplierInvoiceModel {
            $supplierId = (int) $data['supplier_id'];
            $this->assertSupplierEntity($supplierId);

            $status = (string) $data['status'];
            $sendPaymentReceiptEmail = (bool) Arr::get($data, 'send_payment_receipt_email', false);

            $invoice = SupplierInvoiceModel::query()->create([
                'number' => (string) $data['number'],
                'invoice_date' => (string) $data['invoice_date'],
                'due_date' => (string) $data['due_date'],
                'supplier_id' => $supplierId,
                'supplier_order_id' => Arr::has($data, 'supplier_order_id') && $data['supplier_order_id'] !== null
                    ? (int) $data['supplier_order_id']
                    : null,
                'total_amount' => number_format((float) $data['total_amount'], 2, '.', ''),
                'status' => $status,
            ]);

            $this->storeDocumentFile($invoice, $files['document_file'] ?? null, $userId);
            $createdPaymentProof = $this->storePaymentProofFile($invoice, $files['payment_proof_file'] ?? null, $userId);

            if ($status === 'paid') {
                $this->assertPaymentProofPresent($createdPaymentProof);
                $this->sendPaymentReceiptEmailIfRequested($invoice, $createdPaymentProof, $sendPaymentReceiptEmail);
            }

            return $this->getById((int) $invoice->getKey());
        });
    }

    public function update(int $id, array $data, array $files, int $userId): SupplierInvoiceModel
    {
        return DB::transaction(function () use ($id, $data, $files, $userId): SupplierInvoiceModel {
            $invoice = SupplierInvoiceModel::query()->findOrFail($id);
            $currentStatus = (string) $invoice->status;
            $nextStatus = (string) Arr::get($data, 'status', $currentStatus);
            $sendPaymentReceiptEmail = (bool) Arr::get($data, 'send_payment_receipt_email', false);
            $payload = [];

            if (array_key_exists('number', $data)) {
                $payload['number'] = (string) $data['number'];
            }
            if (array_key_exists('invoice_date', $data)) {
                $payload['invoice_date'] = (string) $data['invoice_date'];
            }
            if (array_key_exists('due_date', $data)) {
                $payload['due_date'] = (string) $data['due_date'];
            }
            if (array_key_exists('supplier_id', $data)) {
                $supplierId = (int) $data['supplier_id'];
                $this->assertSupplierEntity($supplierId);
                $payload['supplier_id'] = $supplierId;
            }
            if (array_key_exists('supplier_order_id', $data)) {
                $payload['supplier_order_id'] = $data['supplier_order_id'] !== null ? (int) $data['supplier_order_id'] : null;
            }
            if (array_key_exists('total_amount', $data)) {
                $payload['total_amount'] = number_format((float) $data['total_amount'], 2, '.', '');
            }
            if (array_key_exists('status', $data)) {
                $payload['status'] = (string) $data['status'];
            }

            if ($payload !== []) {
                $invoice->update($payload);
            }

            $this->storeDocumentFile($invoice, $files['document_file'] ?? null, $userId);
            $createdPaymentProof = $this->storePaymentProofFile($invoice, $files['payment_proof_file'] ?? null, $userId);
            $effectivePaymentProof = $createdPaymentProof ?? $this->findLatestPaymentProofAttachment((int) $invoice->getKey());

            if ($nextStatus === 'paid') {
                $isPaidTransition = $currentStatus !== 'paid';
                if ($isPaidTransition) {
                    $this->assertPaymentProofPresent($effectivePaymentProof);
                }
                $this->sendPaymentReceiptEmailIfRequested($invoice, $effectivePaymentProof, $sendPaymentReceiptEmail);
            }

            return $this->getById((int) $invoice->getKey());
        });
    }

    public function getAttachment(int $invoiceId, int $fileId): DigitalFileModel
    {
        return DigitalFileModel::query()
            ->whereKey($fileId)
            ->where('fileable_id', $invoiceId)
            ->where('fileable_type', SupplierInvoiceModel::class)
            ->firstOrFail();
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $invoice = SupplierInvoiceModel::query()
                ->with(['files:id,file_path,fileable_id,fileable_type'])
                ->findOrFail($id);

            foreach ($invoice->files as $file) {
                $this->digitalFileService->delete($file);
            }

            $invoice->delete();
        });
    }

    private function storeDocumentFile(
        SupplierInvoiceModel $invoice,
        ?UploadedFile $file,
        int $userId
    ): ?DigitalFileModel {
        return $this->storeAttachmentByCategory(
            $invoice,
            $file,
            'supplier-invoice-document',
            'Documento Fatura '.$invoice->number,
            $userId
        );
    }

    private function storePaymentProofFile(
        SupplierInvoiceModel $invoice,
        ?UploadedFile $file,
        int $userId
    ): ?DigitalFileModel {
        return $this->storeAttachmentByCategory(
            $invoice,
            $file,
            'supplier-invoice-payment-proof',
            'Comprovativo Fatura '.$invoice->number,
            $userId
        );
    }

    private function storeAttachmentByCategory(
        SupplierInvoiceModel $invoice,
        ?UploadedFile $file,
        string $category,
        string $name,
        int $userId
    ): ?DigitalFileModel {
        if (! $file) {
            return null;
        }

        return $this->digitalFileService->store([
            'name' => $name,
            'category' => $category,
            'entity_id' => $invoice->supplier_id,
            'fileable_id' => $invoice->id,
            'fileable_type' => SupplierInvoiceModel::class,
        ], $file, $userId);
    }

    private function assertSupplierEntity(int $supplierId): void
    {
        $isSupplier = EntityModel::query()
            ->whereKey($supplierId)
            ->where('is_supplier', true)
            ->exists();

        if ($isSupplier) {
            return;
        }

        throw ValidationException::withMessages([
            'supplier_id' => 'A entidade selecionada não é um fornecedor.',
        ]);
    }

    private function findLatestPaymentProofAttachment(int $invoiceId): ?DigitalFileModel
    {
        return DigitalFileModel::query()
            ->where('fileable_id', $invoiceId)
            ->where('fileable_type', SupplierInvoiceModel::class)
            ->where('category', 'supplier-invoice-payment-proof')
            ->latest('id')
            ->first();
    }

    private function assertPaymentProofPresent(?DigitalFileModel $paymentProof): void
    {
        if ($paymentProof !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'payment_proof_file' => 'Ao marcar como paga, é obrigatório anexar comprovativo de pagamento.',
        ]);
    }

    private function sendPaymentReceiptEmailIfRequested(
        SupplierInvoiceModel $invoice,
        ?DigitalFileModel $paymentReceipt,
        bool $sendPaymentReceiptEmail
    ): void {
        if (! $sendPaymentReceiptEmail) {
            return;
        }

        if ($paymentReceipt === null) {
            throw ValidationException::withMessages([
                'payment_proof_file' => 'Não é possível enviar email sem comprovativo de pagamento.',
            ]);
        }

        $supplierEmail = trim((string) optional($invoice->supplier)->email);
        if ($supplierEmail === '') {
            throw ValidationException::withMessages([
                'supplier_id' => 'O fornecedor selecionado não tem email configurado.',
            ]);
        }

        Mail::to($supplierEmail)->send(new SupplierInvoicePaymentReceiptMail($invoice, $paymentReceipt));
    }
}
