<?php

namespace App\Http\Resources\Settings\Logs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->created_at?->format('d/m/Y'),
            'time' => $this->created_at?->format('H:i'),
            'user_name' => $this->causer?->name ?? 'Sistema',
            'menu' => $this->log_name ?? 'system',
            'action' => $this->description ?? 'unknown',
            'device' => $this->properties['device'] ?? 'Unknown',
            'ip_address' => $this->properties['ip'] ?? 'N/A',
        ];
    }
}
