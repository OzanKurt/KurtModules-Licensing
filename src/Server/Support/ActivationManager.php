<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Server\Exceptions\ActivationLimitReachedException;
use Kurt\Modules\Licensing\Server\Exceptions\LicenseNotUsableException;
use Kurt\Modules\Licensing\Server\Models\Activation;
use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Owns the per-machine seat lifecycle. Re-activating a machine that already
 * holds a seat is idempotent (it just refreshes the heartbeat) so reinstalls
 * never silently burn seats; a genuinely new machine consumes one only if a
 * seat is free.
 */
final class ActivationManager
{
    public function __construct(private readonly EventLogger $events) {}

    /**
     * @param  array<string, string|null>  $meta  Optional label / ip / user_agent.
     */
    public function activate(License $license, string $fingerprint, array $meta = []): Activation
    {
        if (! $license->isUsable()) {
            throw LicenseNotUsableException::withReason($this->reason($license));
        }

        $hash = $this->fingerprintHash($fingerprint);

        try {
            return DB::transaction(function () use ($license, $hash, $meta): Activation {
                // Serialize concurrent activations of the same license: the row
                // lock forces competing transactions to queue here, so the seat
                // check + insert below cannot interleave and overshoot the cap
                // (TOCTOU race). Re-reading activations inside the lock keeps the
                // count authoritative.
                License::whereKey($license->getKey())->lockForUpdate()->first();

                $existing = $license->activations()->where('fingerprint_hash', $hash)->first();

                if ($existing !== null && $existing->isActive()) {
                    $existing->update([
                        'last_seen_at' => now(),
                        'ip' => $meta['ip'] ?? $existing->ip,
                        'user_agent' => $meta['user_agent'] ?? $existing->user_agent,
                    ]);

                    return $existing;
                }

                if (! $license->hasAvailableSeat()) {
                    throw ActivationLimitReachedException::forSeats($license->max_activations);
                }

                $attributes = [
                    'fingerprint_hash' => $hash,
                    'label' => $meta['label'] ?? ($existing->label ?? null),
                    'ip' => $meta['ip'] ?? null,
                    'user_agent' => $meta['user_agent'] ?? null,
                    'activated_at' => now(),
                    'last_seen_at' => now(),
                    'deactivated_at' => null,
                ];

                if ($existing !== null) {
                    $existing->update($attributes);
                    $activation = $existing;
                } else {
                    $activation = $license->activations()->create($attributes);
                }

                $this->events->log($license, LicenseEventType::Activated, ['fingerprint' => $hash]);

                return $activation;
            });
        } catch (ActivationLimitReachedException $e) {
            // Logged outside the rolled-back transaction so the audit record persists.
            $this->events->log($license, LicenseEventType::LimitReached, ['fingerprint' => $hash]);

            throw $e;
        }
    }

    public function deactivate(License $license, string $fingerprint): bool
    {
        $hash = $this->fingerprintHash($fingerprint);

        $activation = $license->activations()
            ->where('fingerprint_hash', $hash)
            ->whereNull('deactivated_at')
            ->first();

        if ($activation === null) {
            return false;
        }

        $activation->update(['deactivated_at' => now()]);
        $this->events->log($license, LicenseEventType::Deactivated, ['fingerprint' => $hash]);

        return true;
    }

    public function fingerprintHash(string $fingerprint): string
    {
        return hash('sha256', $fingerprint);
    }

    private function reason(License $license): string
    {
        return match (true) {
            $license->status === LicenseStatus::Revoked => 'revoked',
            $license->status === LicenseStatus::Suspended => 'suspended',
            $license->hasExpired() => 'expired',
            default => 'inactive',
        };
    }
}
