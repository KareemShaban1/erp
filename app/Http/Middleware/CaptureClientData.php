<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CaptureClientData
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get Google API Key from .env
        // $googleApiKey = env('GOOGLE_MAP_API_KEY');
        $googleApiKey = 'AIzaSyBUK88jmlcZv3IdJlhp944cJmzkWKelqq4';
        // Get user IP
        $ip = $request->ip();

        // Fetch location based on IP (Alternative to Google Geolocation API)
        $ipLocation = Http::get("http://ipinfo.io/{$ip}/json")->json();

        if (isset($ipLocation['loc'])) {
            // Extract lat/lng
            [$lat, $lng] = explode(',', $ipLocation['loc']);

            // Convert lat/lon to address using Google Geocoding API
            $addressResponse = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$googleApiKey}")->json();

            // Get formatted address or fallback to 'Address not found'
            $formattedAddress = $addressResponse['results'][0]['formatted_address'] ?? 'Address not found';

            // Attach user data to the request
            $request->merge([
                'user_data' => [
                    'ip' => $ip,
                    'user_agent' => $request->header('User-Agent'),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => $formattedAddress
                ]
            ]);

            // Log client data for debugging
            \Log::info('Client Data:', $request->user_data);
        } else {
            \Log::error('Failed to retrieve location for IP: ' . $ip);
        }

        // Continue to the next middleware
        return $next($request);
    }
}
