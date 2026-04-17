<?php

namespace App\Http\Controllers\Api\V1\DigitalArchive;

use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalArchive\StoreDigitalFileRequest;
use App\Http\Resources\DigitalArchive\DigitalFileResource;
use App\Models\DigitalFileModel;
use App\Services\DigitalArchive\DigitalFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigitalFileControllerAPI extends Controller
{
    public function __construct(
        protected DigitalFileService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $files = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
        ]));

        return response()->json([
            'message' => 'Lista de ficheiros',
            'data' => DigitalFileResource::collection($files),
        ], 200);
    }

    public function store(StoreDigitalFileRequest $request): JsonResponse
    {
        $file = $this->service->store(
            $request->validated(),
            $request->file('file'),
            (int) $request->user()->id
        );
        $file->load(['uploader:id,name,email', 'entity:id,name']);

        return response()->json([
            'message' => 'Ficheiro carregado com sucesso',
            'data' => new DigitalFileResource($file),
        ], 201);
    }

    public function download(DigitalFileModel $digitalFile): mixed
    {
        return $this->service->download($digitalFile);
    }

    public function destroy(DigitalFileModel $digitalFile): JsonResponse
    {
        $this->service->delete($digitalFile);

        return response()->json([
            'message' => 'Ficheiro removido com sucesso',
        ], 200);
    }
}
