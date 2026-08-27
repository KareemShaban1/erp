<?php

/**
 * Polyfill for guzzlehttp/psr7 1.x helpers removed in 2.x.
 * Needed by older kreait/firebase-php (e.g. 5.9) without upgrading packages.
 */

namespace GuzzleHttp\Psr7;

if (! function_exists('GuzzleHttp\\Psr7\\uri_for')) {
    function uri_for($uri)
    {
        return Utils::uriFor($uri);
    }
}

if (! function_exists('GuzzleHttp\\Psr7\\stream_for')) {
    function stream_for($resource = '', array $options = [])
    {
        return Utils::streamFor($resource, $options);
    }
}
