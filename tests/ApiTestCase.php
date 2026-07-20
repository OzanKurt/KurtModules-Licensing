<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Tests;

use Illuminate\Foundation\Application;

/**
 * Boots the package with the REST API kit enabled (`licensing.http.mode = api`)
 * so the `api/licensing/*` routes are registered. The default TestCase leaves
 * the module headless, matching production's safe default.
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('licensing.http.mode', 'api');
    }
}
