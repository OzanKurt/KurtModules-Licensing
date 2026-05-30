<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Models;

use Database\Factories\Kurt\Modules\Licensing\Server\ActivationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One machine/seat a license is installed on, identified by an opaque
 * `fingerprint_hash`. A row with a null `deactivated_at` occupies a seat;
 * deactivating frees it. The (license_id, fingerprint_hash) unique index makes
 * re-activating the same machine idempotent rather than seat-consuming.
 *
 * @property int $id
 * @property int $license_id
 * @property string $fingerprint_hash
 * @property string|null $label
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $activated_at
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read License $license
 */
class Activation extends Model
{
    /** @use HasFactory<ActivationFactory> */
    use HasFactory;

    protected $table = 'licensing_activations';

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'fingerprint_hash',
        'label',
        'ip',
        'user_agent',
        'activated_at',
        'last_seen_at',
        'deactivated_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'activated_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    protected static function newFactory(): ActivationFactory
    {
        return ActivationFactory::new();
    }

    /**
     * @return BelongsTo<License, $this>
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * @param  Builder<Activation>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }
}
