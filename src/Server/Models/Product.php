<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Models;

use Database\Factories\Kurt\Modules\Licensing\Server\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A sellable, licensable product — typically one premium Composer package (or a
 * bundle). Holds the default policy applied to newly issued licenses and the
 * list of Composer package names the Composer auth bridge will gate.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property list<string>|null $composer_packages
 * @property array<string, mixed>|null $default_policy
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $table = 'licensing_products';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'composer_packages',
        'default_policy',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'composer_packages' => 'array',
        'default_policy' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
