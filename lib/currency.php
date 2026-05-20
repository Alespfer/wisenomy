<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const FX_TTL = 43200; // 12h in seconds

// All currencies supported by Frankfurter (BCE), with human labels
const CURRENCIES = [
    'EUR' => 'Euro',
    'USD' => 'US Dollar',
    'GBP' => 'British Pound',
    'CHF' => 'Swiss Franc',
    'SEK' => 'Swedish Krona',
    'NOK' => 'Norwegian Krone',
    'DKK' => 'Danish Krone',
    'ISK' => 'Icelandic Króna',
    'PLN' => 'Polish Złoty',
    'CZK' => 'Czech Koruna',
    'HUF' => 'Hungarian Forint',
    'RON' => 'Romanian Leu',
    'TRY' => 'Turkish Lira',
    'ILS' => 'Israeli Shekel',
    'JPY' => 'Japanese Yen',
    'CNY' => 'Chinese Yuan',
    'KRW' => 'South Korean Won',
    'HKD' => 'Hong Kong Dollar',
    'SGD' => 'Singapore Dollar',
    'THB' => 'Thai Baht',
    'IDR' => 'Indonesian Rupiah',
    'MYR' => 'Malaysian Ringgit',
    'PHP' => 'Philippine Peso',
    'INR' => 'Indian Rupee',
    'AUD' => 'Australian Dollar',
    'NZD' => 'New Zealand Dollar',
    'CAD' => 'Canadian Dollar',
    'MXN' => 'Mexican Peso',
    'BRL' => 'Brazilian Real',
    'ZAR' => 'South African Rand',
];

function fx_get(string $base, string $quote): array {
    if ($base === $quote) {
        return ['rate' => 1.0, 'fetched_at' => date('Y-m-d H:i:s'), 'stale' => false];
    }

    $db  = db();
    $row = $db->prepare('SELECT rate, fetched_at FROM fx_cache WHERE base=? AND quote=?');
    $row->execute([$base, $quote]);
    $cached = $row->fetch();

    $fresh = false;
    if ($cached) {
        $age   = time() - strtotime($cached['fetched_at']);
        $fresh = $age < FX_TTL;
    }

    if ($fresh) {
        return ['rate' => $cached['rate'], 'fetched_at' => $cached['fetched_at'], 'stale' => false];
    }

    // Attempt live fetch
    $rate = fx_fetch_live($base, $quote);

    if ($rate !== null) {
        $now = date('Y-m-d H:i:s');
        $db->prepare('INSERT INTO fx_cache(base,quote,rate,fetched_at) VALUES(?,?,?,?)
                      ON CONFLICT(base,quote) DO UPDATE SET rate=excluded.rate, fetched_at=excluded.fetched_at')
           ->execute([$base, $quote, $rate, $now]);
        return ['rate' => $rate, 'fetched_at' => $now, 'stale' => false];
    }

    // Fallback to stale cache
    if ($cached) {
        return ['rate' => $cached['rate'], 'fetched_at' => $cached['fetched_at'], 'stale' => true];
    }

    throw new RuntimeException("No FX data for $base/$quote and no cached fallback.");
}

function fx_fetch_live(string $base, string $quote): ?float {
    $url = "https://api.frankfurter.dev/v1/latest?base={$base}&symbols={$quote}";
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return null;
    $data = json_decode($body, true);
    return isset($data['rates'][$quote]) ? (float)$data['rates'][$quote] : null;
}

// Convert amount_cents in $from to cents in $to.
// Returns ['cents' => int, 'rate' => float, 'fetched_at' => string, 'stale' => bool]
function fx_convert(int $cents, string $from, string $to): array {
    $fx = fx_get($from, $to);
    $converted = (int)round($cents * $fx['rate']);
    return [
        'cents'      => $converted,
        'rate'       => $fx['rate'],
        'fetched_at' => $fx['fetched_at'],
        'stale'      => $fx['stale'],
    ];
}
