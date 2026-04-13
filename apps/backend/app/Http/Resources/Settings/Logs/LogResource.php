<?php

namespace App\Http\Resources\Settings\Logs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->created_at?->format('d/m/Y'),
            'time' => $this->created_at?->format('H:i'),
            'user_name' => $this->causer?->name ?? 'Sistema',
            'menu' => $this->formatMenu($this->log_name),
            'action' => $this->formatAction($this->description),
            'device' => $this->properties['device'] ?? 'Unknown',
            'ip_address' => $this->properties['ip'] ?? 'N/A',
        ];
    }

    private function formatMenu(?string $menu): string
    {
        if (! $menu) {
            return 'Sistema';
        }

        return ucfirst($menu);
    }

    private function formatAction(?string $action): string
    {
        return match ($action) {
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Inativado',
            default => $action ?? 'N/A',
        };
    }
}
