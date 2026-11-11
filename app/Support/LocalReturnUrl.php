<?php

namespace App\Support;

final class LocalReturnUrl
{
    public static function resolve(mixed $value, string $fallback): string
    {
        if (! is_string($value) || $value === '') {
            return $fallback;
        }

        $decoded = rawurldecode($value);
        if (preg_match('/[\x00-\x1f\x7f\\\\]/', $decoded) || str_starts_with($decoded, '//')) {
            return $fallback;
        }

        $base = parse_url(url('/'));
        $target = parse_url($value);
        if ($target === false || isset($target['user']) || isset($target['pass'])) {
            return $fallback;
        }
        if (isset($target['scheme']) || isset($target['host'])) {
            if (strtolower($target['scheme'] ?? '') !== strtolower($base['scheme'] ?? '')
                || strtolower($target['host'] ?? '') !== strtolower($base['host'] ?? '')
                || ($target['port'] ?? null) !== ($base['port'] ?? null)) {
                return $fallback;
            }
        } elseif (! str_starts_with($value, '/')) {
            return $fallback;
        }

        $path = rawurldecode($target['path'] ?? '/');
        $basePath = rtrim($base['path'] ?? '', '/');
        if (($basePath !== '' && $path !== $basePath && ! str_starts_with($path, $basePath.'/'))
            || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $path)) {
            return $fallback;
        }

        return $value;
    }
}
