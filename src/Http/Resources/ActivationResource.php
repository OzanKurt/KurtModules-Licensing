<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Licensing\Server\Models\Activation;

/**
 * Admin-facing representation of a seat activation. Never exposes the opaque
 * `fingerprint_hash` — only the operator-supplied label and last-seen metadata.
 *
 * @mixin Activation
 */
final class ActivationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'active' => $this->isActive(),
            'activated_at' => $this->activated_at->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'deactivated_at' => $this->deactivated_at?->toIso8601String(),
        ];
    }
}
