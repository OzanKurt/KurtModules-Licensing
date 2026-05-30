<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Licensing\Crypto\Ed25519;

final class GenerateKeysCommand extends Command
{
    protected $signature = 'licensing:keygen';

    protected $description = 'Generate an Ed25519 signing keypair for signed license files.';

    public function handle(): int
    {
        $pair = Ed25519::generateKeyPair();

        $this->info('Add these to your .env — keep LICENSING_SIGNING_KEY secret (server only):');
        $this->newLine();
        $this->line('LICENSING_SIGNING_KEY='.$pair['secret']);
        $this->line('LICENSING_PUBLIC_KEY='.$pair['public']);
        $this->newLine();
        $this->comment('Premium packages embedding the client SDK only need the public key.');

        return self::SUCCESS;
    }
}
