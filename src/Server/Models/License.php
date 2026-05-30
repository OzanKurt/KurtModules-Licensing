<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Models;

use Database\Factories\Kurt\Modules\Licensing\Server\LicenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Enums\PolicyType;

/**
 * A single issued license. The plaintext key is never stored — only its
 * `key_hash` (lookup) and `key_prefix` (human-friendly support reference).
 * Entitlement is the product of three independent dimensions: lifecycle
 * `status`, time-based policy (`policy_type` + dates), and seat usage
 * (`max_activations` vs active activations).
 *
 * @property int $id
 * @property int $product_id
 * @property string $key_hash
 * @property string $key_prefix
 * @property string $licensee_email
 * @property int|null $licensee_user_id
 * @property string|null $licensee_name
 * @property string|null $licensee_company
 * @property LicenseStatus $status
 * @property PolicyType $policy_type
 * @property int $max_activations
 * @property Carbon $issued_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $updates_until
 * @property string|null $order_reference
 * @property Carbon|null $revoked_at
 * @property string|null $revoked_reason
 * @property array<string, mixed>|null $metadata
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product $product
 */
class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'licensing_licenses';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'key_hash',
        'key_prefix',
        'licensee_email',
        'licensee_user_id',
        'licensee_name',
        'licensee_company',
        'status',
        'policy_type',
        'max_activations',
        'issued_at',
        'expires_at',
        'updates_until',
        'order_reference',
        'revoked_at',
        'revoked_reason',
        'metadata',
        'notes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => LicenseStatus::class,
        'policy_type' => PolicyType::class,
        'max_activations' => 'integer',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'updates_until' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function newFactory(): LicenseFactory
    {
        return LicenseFactory::new();
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<Activation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    /**
     * @return HasMany<LicenseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(LicenseEvent::class);
    }

    /**
     * True only when the license may currently run: active status AND not
     * past its subscription expiry. Seat limits are checked separately at
     * activation time, not here.
     */
    public function isUsable(): bool
    {
        return $this->status->isUsable() && ! $this->hasExpired();
    }

    /**
     * Time-based expiry. Only subscriptions expire at runtime; perpetual and
     * updates-window licenses run forever (the latter only gates downloads).
     */
    public function hasExpired(): bool
    {
        if ($this->policy_type === PolicyType::Subscription && $this->expires_at !== null) {
            return $this->expires_at->isPast();
        }

        return false;
    }

    public function activeActivationsCount(): int
    {
        return $this->activations()->whereNull('deactivated_at')->count();
    }

    public function hasAvailableSeat(): bool
    {
        return $this->activeActivationsCount() < $this->max_activations;
    }

    /**
     * Whether a release published on `$releaseDate` may be downloaded under
     * this license — the rule the Composer auth bridge enforces per package
     * version.
     */
    public function allowsDownloadOf(Carbon $releaseDate): bool
    {
        return match ($this->policy_type) {
            PolicyType::Perpetual => true,
            PolicyType::Subscription => ! $this->hasExpired(),
            PolicyType::UpdatesWindow => $this->updates_until === null
                || $releaseDate->lessThanOrEqualTo($this->updates_until),
        };
    }

    /**
     * @param  Builder<License>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', LicenseStatus::Active->value);
    }

    /**
     * @param  Builder<License>  $query
     */
    public function scopeForKeyHash(Builder $query, string $keyHash): void
    {
        $query->where('key_hash', $keyHash);
    }
}
