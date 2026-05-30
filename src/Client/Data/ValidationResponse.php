<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client\Data;

/**
 * Normalised result of an online validate/activate call. `$source` records
 * whether it came fresh from the server or was replayed from the offline cache.
 */
final readonly class ValidationResponse
{
    /**
     * @param  array<array-key, mixed>  $claims
     */
    public function __construct(
        public bool $valid,
        public ?string $reason = null,
        public array $claims = [],
        public string $source = 'server',
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data, string $source = 'server'): self
    {
        return new self(
            (bool) ($data['valid'] ?? false),
            isset($data['reason']) && is_string($data['reason']) ? $data['reason'] : null,
            is_array($data['claims'] ?? null) ? $data['claims'] : [],
            $source,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'reason' => $this->reason,
            'claims' => $this->claims,
        ];
    }
}
