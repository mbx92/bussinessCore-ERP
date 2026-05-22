<?php

namespace App\Http\Middleware;

use App\Models\ErpSetting;
use App\Services\AppInstallationService;
use App\Support\EnabledModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly AppInstallationService $installationService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installationService->status()['installed']) {
            return $next($request);
        }

        $setting = ErpSetting::query()->first();
        if (! $setting) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $moduleKey = EnabledModuleRegistry::moduleForRouteName($routeName);

        if ($moduleKey === null || $setting->isModuleEnabled($moduleKey)) {
            return $next($request);
        }

        if ($request->user() && $request->isMethod('GET')) {
            return redirect()
                ->route('dashboard')
                ->with('flash', [
                    'type' => 'warning',
                    'message' => sprintf('Modul %s belum diaktifkan pada installer.', EnabledModuleRegistry::installableModules()[$moduleKey]['label'] ?? $moduleKey),
                ]);
        }

        abort(404);
    }
}
