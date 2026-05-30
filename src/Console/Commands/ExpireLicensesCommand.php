<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Enums\PolicyType;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Support\EventLogger;

/**
 * Flips active subscriptions whose expiry has passed to the Expired status and
 * audits each transition. Runtime checks already treat them as expired live;
 * this just makes the stored status reflect reality for reporting and admin UI.
 * Scheduled daily.
 */
final class ExpireLicensesCommand extends Command
{
    protected $signature = 'licensing:expire';

    protected $description = 'Mark active subscriptions past their expiry as expired.';

    public function handle(EventLogger $events): int
    {
        $expired = 0;

        License::query()
            ->where('status', LicenseStatus::Active->value)
            ->where('policy_type', PolicyType::Subscription->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (License $license) use ($events, &$expired): void {
                $license->update(['status' => LicenseStatus::Expired->value]);
                $events->log($license, LicenseEventType::Expired);
                $expired++;
            });

        $this->info("Expired {$expired} license(s).");

        return self::SUCCESS;
    }
}
