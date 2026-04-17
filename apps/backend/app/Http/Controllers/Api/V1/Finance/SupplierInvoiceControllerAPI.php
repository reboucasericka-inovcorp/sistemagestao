<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSupplierInvoiceRequest;
use App\Http\Requests\Finance\UpdateSupplierInvoiceRequest;
use App\Http\Resources\Finance\SupplierInvoiceResource;
use App\Services\DigitalArchive\DigitalFileService;
use App\Services\Finance\SupplierInvoiceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierInvoiceControllerAPI extends Controller
{
    public function __construct(
        protected SupplierInvoiceService $service,
        protected DigitalFileService $digitalFileService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->service->getAll($request->only([
            'search',
            'status',
            'supplier_id',
            'supplier_order_id',
            'date_from',
            'date_to',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));

        $serializedItems = SupplierInvoiceResource::collection($invoices->items())->resolve($request);

        return ApiResponse::success('Supplier invoices retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function store(StoreSupplierInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $files = [
            'document_file' => $request->file('document_file'),
            'payment_proof_file' => $request->file('payment_proof_file'),
        ];

        $invoice = $this->service->create(
            $data,
            $files,
            (int) $request->user()->id
        );

        return ApiResponse::success('Supplier invoice created successfully', new SupplierInvoiceResource($invoice), 201);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->service->getById($id);

        return ApiResponse::success('Supplier invoice retrieved successfully', new SupplierInvoiceResource($invoice));
    }

    public function update(UpdateSupplierInvoiceRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $files = [
            'document_file' => $request->file('document_file'),
            'payment_proof_file' => $request->file('payment_proof_file'),
        ];

        $invoice = $this->service->update(
            $id,
            $data,
            $files,
            (int) $request->user()->id
        );

        return ApiResponse::success('Supplier invoice updated successfully', new SupplierInvoiceResource($invoice));
    }

    public function download(int $id, int $fileId): mixed
    {
        $file = $this->service->getAttachment($id, $fileId);

        return $this->digitalFileService->download($file);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::success('Supplier invoice deleted successfully', null);
    }
}
