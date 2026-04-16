<?php

namespace App\Http\Resources\WorkOrders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'date' => optional($this->date)->toDateString(),
            'status' => $this->status,
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'client' => $this->client
                ? [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'number' => $this->client->number,
                ]
                : null,
            'items' => $this->whenLoaded('items', function (): array {
                return $this->items->map(static function ($item): array {
                    return [
                        'id' => $item->id,
                        'article_id' => $item->article_id,
                        'article' => $item->article
                            ? [
                                'id' => $item->article->id,
                                'name' => $item->article->name,
                                'reference' => $item->article->reference,
                            ]
                            : null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                })->values()->all();
            }),
        ];
    }
}
