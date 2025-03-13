<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Http;

class CaptureClientData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get User IP
        $ip = $request->ip();

        // Get User Agent
        $userAgent = $request->header('User-Agent');

        // Get Location (Using ip-api.com)
        $location = Http::get("http://ip-api.com/json/{$ip}")->json();

        // Attach user data to the request object
        $request->merge([
            'user_data' => [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'location' => $location
            ]
        ]);

        \Log::info('client_data',[$request]);

        return $next($request);
    
    }
}
