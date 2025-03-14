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

        // Get User IP
        $ip = $request->ip();

        // Get User Agent
        $userAgent = $request->header('User-Agent');

        // Get Approximate Location using Google Geolocation API
        $locationResponse = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://www.googleapis.com/geolocation/v1/geolocate?key={$googleApiKey}", []);
        
        
        \Log::info('locationResponse',[$locationResponse]);
        if (isset($locationResponse['location'])) {
            $lat = $locationResponse['location']['lat'];
            $lng = $locationResponse['location']['lng'];

            // Convert Lat/Lon to Address using Google Geocoding API
            $addressResponse = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$googleApiKey}")->json();

            $formattedAddress = $addressResponse['results'][0]['formatted_address'] ?? 'Address not found';

            // Attach user data to the request
            $request->merge([
                'user_data' => [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => $formattedAddress
                ]
            ]);

            // Log client data
            \Log::info('Client Data:', $request->user_data);
        } else {
            \Log::error('Failed to retrieve location');
        }

        return $next($request);
    }
}
