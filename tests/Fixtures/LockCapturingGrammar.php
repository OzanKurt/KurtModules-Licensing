<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Tests\Fixtures;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

/**
 * Test-only query grammar that makes a `lockForUpdate()` visible under SQLite.
 *
 * SQLite has no `FOR UPDATE`, so the stock grammar silently compiles the lock
 * away — which is exactly why a seat-race regression cannot rely on real
 * locking here. This grammar instead renders the lock as a harmless trailing
 * SQL comment: SQLite ignores it, but it shows up verbatim in the query log, so
 * a test can assert the `ActivationManager` still asks for the row lock it
 * depends on and catch anyone silently removing it.
 */
final class LockCapturingGrammar extends SQLiteGrammar
{
    /**
     * @param  bool|string  $value
     */
    protected function compileLock(Builder $query, $value): string
    {
        if ($value === true || $value === 'for update') {
            return '/* for update */';
        }

        return parent::compileLock($query, $value);
    }
}
