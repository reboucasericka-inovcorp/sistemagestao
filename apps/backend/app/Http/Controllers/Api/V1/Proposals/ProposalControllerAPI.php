<?php

namespace App\Http\Controllers\Api\V1\Proposals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proposals\StoreProposalRequest;
use App\Http\Requests\Proposals\UpdateProposalRequest;
use App\Http\Resources\Proposals\ProposalResource;
use App\Services\Proposals\ProposalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProposalControllerAPI extends Controller
{
    public function __construct(
        protected ProposalService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $proposals = $this->service->getAll($request->only([
            'search',
            'status',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));
        $serializedItems = ProposalResource::collection($proposals->items())->resolve($request);

        return ApiResponse::success('Proposals retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $proposal = $this->service->getById($id);

        return ApiResponse::success('Proposal retrieved successfully', new ProposalResource($proposal));
    }

    public function store(StoreProposalRequest $request): JsonResponse
    {
        $proposal = $this->service->create($request->validated());

        return ApiResponse::success('Proposal created successfully', new ProposalResource($proposal), 201);
    }

    public function update(UpdateProposalRequest $request, int $id): JsonResponse
    {
        $proposal = $this->service->update($id, $request->validated());

        return ApiResponse::success('Proposal updated successfully', new ProposalResource($proposal));
    }

    public function convert(int $id): JsonResponse
    {
        $proposal = $this->service->convertToOrder($id);

        return ApiResponse::success('Proposal converted successfully', new ProposalResource($proposal));
    }

    public function pdf(int $id)
    {
        $pdf = $this->service->generatePdf($id);

        return $pdf->download("proposal_{$id}.pdf");
    }
}
