<?php

namespace App\Http\Resources\Orders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'order_date' => optional($this->order_date)->toDateString(),
            'status' => $this->status,
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
                        'supplier_id' => $item->supplier_id,
                        'supplier' => $item->supplier
                            ? [
                                'id' => $item->supplier->id,
                                'name' => $item->supplier->name,
                                'number' => $item->supplier->number,
                            ]
                            : null,
                        'quantity' => $item->quantity,
                        'cost_price' => $item->cost_price,
                        'total' => $item->total,
                    ];
                })->values()->all();
            }),
        ];
    }
}
