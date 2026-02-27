<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptainTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = trim((string) config('captain.token', ''));

        if ($configuredToken === '') {
            return response()->json([
                'success' => false,
                'error' => 'Service unavailable',
            ], 503);
        }

        $providedToken = trim((string) $request->header(config('captain.token_header', 'X-CAPTAIN-TOKEN'), ''));

        if ($providedToken === '') {
            $authHeader = (string) $request->header('Authorization', '');
            if (str_starts_with($authHeader, 'Bearer ')) {
                $providedToken = trim(substr($authHeader, 7));
            }
        }

        if ($providedToken === '' || strlen($providedToken) > 1024 || !hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
