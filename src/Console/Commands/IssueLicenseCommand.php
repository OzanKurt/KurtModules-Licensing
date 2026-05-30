<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\LicenseIssuer;

final class IssueLicenseCommand extends Command
{
    protected $signature = 'licensing:issue
        {product : Product slug}
        {email : Licensee email}
        {--policy= : perpetual|subscription|updates_window}
        {--seats= : Activation limit}
        {--expires= : Expiry datetime (subscription/updates_window)}';

    protected $description = 'Issue a license for a product and print the plaintext key once.';

    public function handle(LicenseIssuer $issuer): int
    {
        $product = Product::query()->where('slug', $this->stringArgument('product'))->first();

        if ($product === null) {
            $this->error('No product found with that slug.');

            return self::FAILURE;
        }

        $attributes = ['licensee_email' => $this->stringArgument('email')];

        $policy = $this->option('policy');
        if (is_string($policy) && $policy !== '') {
            $attributes['policy_type'] = $policy;
        }

        $seats = $this->option('seats');
        if (is_numeric($seats)) {
            $attributes['max_activations'] = (int) $seats;
        }

        $expires = $this->option('expires');
        if (is_string($expires) && $expires !== '') {
            $attributes['expires_at'] = $expires;
        }

        $issued = $issuer->issue($product, $attributes);

        $this->info('License issued for '.$issued->license->licensee_email.'.');
        $this->line('Key (store it now — it is not recoverable): '.$issued->key);

        return self::SUCCESS;
    }

    private function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }
}
