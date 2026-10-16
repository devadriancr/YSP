<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the sync service (supply-relay) can send data. Each request carries:
 * - X-Sync-Timestamp: Unix time when it was signed.
 * - X-Sync-Signature: hex HMAC-SHA256 of "{timestamp}.{raw body}" with SYNC_KEY.
 *
 * A request is rejected when the key is not configured, the signature does not
 * match (wrong key or body changed on the way), it is too old, or it was
 * already used once (replay).
 */
class VerifySyncSignature
{
    // Shorter keys are easy to guess; generate one with: php -r "echo bin2hex(random_bytes(32));"
    private const MIN_KEY_LENGTH = 32;

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('portal.sync.key');

        abort_if(strlen($key) < self::MIN_KEY_LENGTH, Response::HTTP_SERVICE_UNAVAILABLE, 'Sync is not configured.');

        $allowedIps = config('portal.sync.allowed_ips');
        abort_if($allowedIps && ! IpUtils::checkIp((string) $request->ip(), $allowedIps), Response::HTTP_FORBIDDEN);

        $timestamp = (string) $request->header('X-Sync-Timestamp');
        $signature = (string) $request->header('X-Sync-Signature');

        abort_unless(ctype_digit($timestamp) && $signature !== '', Response::HTTP_UNAUTHORIZED, 'Missing signature.');
        abort_if(abs(time() - (int) $timestamp) > config('portal.sync.tolerance'), Response::HTTP_UNAUTHORIZED, 'Expired signature.');

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $key);

        abort_unless(hash_equals($expected, strtolower($signature)), Response::HTTP_UNAUTHORIZED, 'Invalid signature.');

        // Each signature works once, so a captured request cannot be sent again
        $ttl = config('portal.sync.tolerance') * 2;
        abort_unless(Cache::add('sync-signature:'.$expected, true, $ttl), Response::HTTP_UNAUTHORIZED, 'Signature already used.');

        return $next($request);
    }
}
