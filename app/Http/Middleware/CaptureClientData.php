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
        // Get User IP considering proxies
        $ip = $request->header('X-Forwarded-For') ?? $request->ip();
    
        // If multiple IPs, take the first one
        $ip = explode(',', $ip)[0];
    
        // Get User Agent
        $userAgent = $request->header('User-Agent');
    
        // Get Location Data (Using ip-api.com)
        $location = Http::get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon,isp,query")
                        ->json();
    
        // Validate the response
        if (!isset($location['status']) || $location['status'] !== 'success') {
            $location = ['error' => 'Could not retrieve location data'];
        }
    
        // Attach user data to the request object
        $request->merge([
            'user_data' => [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'location' => $location
            ]
        ]);

        $clientData = [
            'client_data' => [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'location' => $location
            ]
        ];
    
        \Log::info('Client Data', ['client_data' => $request->user_data]);
        \Log::info('Client Data', [$clientData]);

        return $next($request);
    }
    
}
