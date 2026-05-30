<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Target for an nginx `auth_request` subrequest (or a Satis pre-auth check):
 * the AuthenticatesComposer middleware does all the work, so reaching this
 * handler means the license is valid for the requested package — reply 204.
 */
final class ComposerAuthorizeController
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
