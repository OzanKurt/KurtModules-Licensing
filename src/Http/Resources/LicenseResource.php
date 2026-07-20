<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Admin-facing representation of a license. Never exposes the secret material
 * (`key_hash`) — only the human-friendly `key_prefix` support reference.
 *
 * @mixin License
 */
final class LicenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key_prefix' => $this->key_prefix,
            'product' => ProductResource::make($this->whenLoaded('product')),
            'status' => $this->status->value,
            'policy_type' => $this->policy_type->value,
            'max_activations' => $this->max_activations,
            'active_activations' => $this->activeActivationsCount(),
            'licensee' => [
                'email' => $this->licensee_email,
                'user_id' => $this->licensee_user_id,
                'name' => $this->licensee_name,
                'company' => $this->licensee_company,
            ],
            'issued_at' => $this->issued_at->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'updates_until' => $this->updates_until?->toIso8601String(),
            'order_reference' => $this->order_reference,
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revoked_reason' => $this->revoked_reason,
            'metadata' => $this->metadata,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
