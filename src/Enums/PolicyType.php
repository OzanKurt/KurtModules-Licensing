<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Enums;

enum PolicyType: string
{
    /** Works forever; no expiry, no update cutoff. */
    case Perpetual = 'perpetual';

    /** Expires at `expires_at`; lapses block runtime + downloads until renewed. */
    case Subscription = 'subscription';

    /** Runs forever, but Composer downloads of releases newer than `updates_until` are blocked. */
    case UpdatesWindow = 'updates_window';
}
