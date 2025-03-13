<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    //
    public function clientData(Request $request)
    {
        // Get User IP
        $ip = $request->ip(); // Get client IP

        // Get User Agent (Device & Browser info)
        $userAgent = $request->header('User-Agent');

        // Get Location (Using ip-api.com)
        $location = Http::get("http://ip-api.com/json/{$ip}")->json();

        return response()->json([
            'ip' => $ip,
            'user_agent' => $userAgent,
            'location' => $location, // Returns country, city, latitude, longitude, ISP, etc.
        ]);
    }
}
