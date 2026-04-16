<?php

namespace App\Http\Resources\Proposals;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'proposal_date' => optional($this->proposal_date)->toDateString(),
            'valid_until' => optional($this->valid_until)->toDateString(),
            'client' => $this->client
                ? [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'number' => $this->client->number,
                ]
                : null,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
        ];
    }
}
