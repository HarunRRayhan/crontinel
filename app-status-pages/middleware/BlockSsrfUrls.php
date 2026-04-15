<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Block SSRF attempts via user-supplied endpoint URLs.
 * Apply this before storing endpoint URLs in StatusPageEndpointController.
 */
class BlockSsrfUrls
{
    private const BLOCKED_RANGES = [
        '127.',
        '10.',
        '169.254.',   // link-local
        '::1',        // IPv6 loopback
        'localhost',
        '0.0.0.0',
    ];

    // 172.16.0.0/12 and 192.168.0.0/16 need numeric checks
    private const BLOCKED_CIDR = [
        ['172.16.', '172.31.'],
        ['192.168.'],
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $url = $request->input('url');

        if ($url && $this->isBlockedUrl($url)) {
            return back()->withErrors(['url' => 'Private/internal URLs are not allowed.']);
        }

        return $next($request);
    }

    private function isBlockedUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return true;
        }

        // Strip brackets from IPv6
        $host = trim($host, '[]');

        foreach (self::BLOCKED_RANGES as $range) {
            if (str_starts_with($host, $range)) {
                return true;
            }
        }

        // 172.16.x.x – 172.31.x.x
        foreach (range(16, 31) as $octet) {
            if (str_starts_with($host, "172.{$octet}.")) {
                return true;
            }
        }

        // 192.168.x.x
        if (str_starts_with($host, '192.168.')) {
            return true;
        }

        // Resolve hostname and check resolved IP
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            return $this->isBlockedUrl(str_replace($host, $ip, $url));
        }

        return false;
    }
}
