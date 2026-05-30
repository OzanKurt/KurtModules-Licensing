<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Models;

use Database\Factories\Kurt\Modules\Licensing\Server\LicenseEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Licensing\Enums\LicenseEventType;

/**
 * Append-only audit trail for a license: issuance, activation churn,
 * validations, lifecycle changes and every Composer authorize/deny decision.
 * Used for support, abuse detection and customer-facing activity history.
 *
 * @property int $id
 * @property int $license_id
 * @property LicenseEventType $action
 * @property array<string, mixed>|null $context
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read License $license
 */
class LicenseEvent extends Model
{
    /** @use HasFactory<LicenseEventFactory> */
    use HasFactory;

    protected $table = 'licensing_license_events';

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'action',
        'context',
        'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'action' => LicenseEventType::class,
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function newFactory(): LicenseEventFactory
    {
        return LicenseEventFactory::new();
    }

    /**
     * @return BelongsTo<License, $this>
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
