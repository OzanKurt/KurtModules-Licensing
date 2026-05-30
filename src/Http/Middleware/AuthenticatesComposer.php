<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kurt\Modules\Licensing\Server\Support\ComposerAuthValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates Composer downloads using HTTP Basic auth: the buyer's email is the
 * username and the license key the password — exactly what Composer sends when
 * a repository is configured with credentials. Apply it to your private
 * Satis/repository routes, or point an nginx `auth_request` at the bundled
 * `{prefix}/composer/authorize/{package}` endpoint. The authorized License is
 * stashed on the request as `licensing.license` for downstream handlers.
 */
final class AuthenticatesComposer
{
    public function __construct(private readonly ComposerAuthValidator $composer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->getPassword();

        if ($key === null || $key === '') {
            return $this->challenge();
        }

        $package = $request->route('package');
        $package = is_string($package) ? $package : '';

        $decision = $this->composer->authorize($key, $package);

        if (! $decision->allowed) {
            return response()->json([
                'status' => 403,
                'message' => "License does not permit '{$package}' ({$decision->reason}).",
            ], 403);
        }

        $request->attributes->set('licensing.license', $decision->license);

        return $next($request);
    }

    private function challenge(): Response
    {
        $realm = (string) config('licensing.composer.realm', 'Composer');

        return response()->json([
            'status' => 401,
            'message' => 'A license key is required to download this package.',
        ], 401, ['WWW-Authenticate' => "Basic realm=\"{$realm}\""]);
    }
}
