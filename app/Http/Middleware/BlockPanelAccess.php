<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockPanelAccess
{
    public function handle(Request $request, Closure $next, string $settingKey, string $tipo): Response
    {
        if (
            Setting::get($settingKey, false)
            && ! (auth()->user()?->hasRole('super_admin'))
        ) {
            return redirect()->route('access.blocked', ['tipo' => $tipo]);
        }

        return $next($request);
    }
}
