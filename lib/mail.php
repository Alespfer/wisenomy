<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Sends an email through the Resend HTTP API.
// Returns ['ok' => true] on success or ['error' => '...'] on failure.
// If RESEND_API_KEY is not set, falls back to appending the payload to a local log file
// so the developer can inspect it during local development.
function mail_send(string $to, string $subject, string $html, string $text): array {
    $api_key = env('RESEND_API_KEY');
    $from    = env('MAIL_FROM', 'noreply@wisenomy.app');

    if ($api_key === null) {
        // Dev fallback: write to file instead of sending.
        $log = __DIR__ . '/../data/mail.log';
        @mkdir(dirname($log), 0755, true);
        $entry = date('c') . " | to=$to | from=$from | subject=$subject\n"
               . "----- TEXT -----\n$text\n"
               . "----- END  -----\n\n";
        file_put_contents($log, $entry, FILE_APPEND | LOCK_EX);
        return ['ok' => true, 'dev_log' => true];
    }

    $payload = json_encode([
        'from'    => $from,
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $html,
        'text'    => $text,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code >= 200 && $code < 300) return ['ok' => true];
    error_log("mail_send failed (HTTP $code): $err | $resp");
    return ['error' => "Email delivery failed (HTTP $code)."];
}

function mail_render_layout(string $title, string $body_html): string {
    $title_h = htmlspecialchars($title, ENT_QUOTES);
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>$title_h</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;background:#f8fafc;margin:0;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
    <h1 style="background:linear-gradient(135deg,#6366f1,#8b5cf6 50%,#ec4899);-webkit-background-clip:text;background-clip:text;color:transparent;font-size:24px;margin:0 0 24px;">Wisenomy</h1>
    $body_html
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0 16px;">
    <p style="color:#94a3b8;font-size:12px;margin:0;">
      Wisenomy · Proyecto open-source · Si no esperabas este correo, puedes ignorarlo con seguridad.
    </p>
  </div>
</body>
</html>
HTML;
}
