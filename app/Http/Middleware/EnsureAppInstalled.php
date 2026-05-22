<?php

namespace App\Http\Middleware;

use App\Services\AppInstallationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppInstalled
{
    public function __construct(private readonly AppInstallationService $installationService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $installerCompleted = (bool) $request->session()->get('installer_completed', false);
        $installerPreviewRequested = App::environment('local')
            && $request->routeIs('install.show')
            && in_array($request->query('preview'), ['loading', 'error'], true);

        if ($request->is('up') || $request->is('storage/*')) {
            return $next($request);
        }

        if (! $this->installationService->status()['installed']) {
            if ($request->routeIs('install.*') || ($installerCompleted && $request->routeIs('login'))) {
                return $next($request);
            }

            return redirect()->route('install.show');
        }

        if (is_string($routeName) && $request->routeIs('install.*')) {
            if ($installerPreviewRequested) {
                return $next($request);
            }

            if ($installerCompleted && $request->routeIs('install.complete')) {
                return $next($request);
            }

            return redirect()->route($request->user() ? 'dashboard' : 'login');
        }

        return $next($request);
    }
}
