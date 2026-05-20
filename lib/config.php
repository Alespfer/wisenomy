<?php
declare(strict_types=1);

// Reads environment variables with a default fallback.
// Sources: $_ENV (set by web server / Railway), then getenv() (CLI / Docker).
function env(string $key, ?string $default = null): ?string {
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === '' || $v === null) return $default;
    return (string)$v;
}

function env_bool(string $key, bool $default = false): bool {
    $v = env($key);
    if ($v === null) return $default;
    return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
}

function is_production(): bool {
    return env('APP_ENV', 'development') === 'production';
}

// Returns true when the request comes through a trusted reverse proxy
// (Cloudflare → Railway). Controlled by TRUST_PROXY env var.
function trust_proxy(): bool {
    return env_bool('TRUST_PROXY', false);
}

// Resolves the public scheme honouring X-Forwarded-Proto when proxy is trusted.
function request_scheme(): string {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
    if (trust_proxy() && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return 'https';
    return 'http';
}

function request_is_https(): bool {
    return request_scheme() === 'https';
}
