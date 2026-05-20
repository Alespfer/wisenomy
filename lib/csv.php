<?php
declare(strict_types=1);

require_once __DIR__ . '/repo.php';

// CSV format (header row):
// date,type,payer,amount,currency,beneficiary,shares,category,note
//
// - date: YYYY-MM-DD HH:MM:SS (or YYYY-MM-DD)
// - type: expense_equal | expense_shares | expense_full | gift | settlement
// - payer: participant name (must exist in group)
// - amount: decimal, dot or comma
// - currency: 3-letter code
// - beneficiary: participant name (required for expense_full and settlement; empty for others)
// - shares: required only for expense_shares. Pipe-separated "Name:amount" pairs,
//           e.g. "Alice:20|Bob:30|Carlos:40". Amounts in the same currency as the
//           `currency` column and must sum to `amount`.
// - category, note: free text

const CSV_HEADERS = ['date','type','payer','amount','currency','beneficiary','shares','category','note'];

function csv_export_stream(int $group_id): void {
    $txs   = tx_list($group_id);
    $parts = participant_list($group_id);
    $pmap  = [];
    foreach ($parts as $p) $pmap[(int)$p['id']] = $p['name'];

    // Pre-fetch beneficiary (for full/settlement) and shares (for expense_shares)
    $ben_map    = [];
    $shares_map = [];
    $sh_stmt    = db()->prepare('SELECT participant_id, share_cents FROM transaction_shares WHERE transaction_id=?');
    foreach ($txs as $tx) {
        $sh_stmt->execute([$tx['id']]);
        $shares = $sh_stmt->fetchAll();
        if (in_array($tx['type'], ['expense_full','settlement'], true)) {
            $ben_map[(int)$tx['id']] = isset($shares[0]) ? ($pmap[(int)$shares[0]['participant_id']] ?? '') : '';
        }
        if ($tx['type'] === 'expense_shares' && !empty($shares)) {
            // Stored share_cents are in base currency (proportional). Recover the
            // original-currency amounts by inverting the ratio: amount_cents / amount_base_cents.
            $orig_total = (int)$tx['amount_cents'];
            $base_total = (int)$tx['amount_base_cents'];
            $pairs = [];
            $accum = 0;
            $n = count($shares);
            foreach ($shares as $i => $sh) {
                if ($i < $n - 1) {
                    $orig = $base_total > 0
                        ? (int)round((int)$sh['share_cents'] * $orig_total / $base_total)
                        : 0;
                    $accum += $orig;
                } else {
                    // Last gets the residual to guarantee exact sum
                    $orig = $orig_total - $accum;
                }
                $name = $pmap[(int)$sh['participant_id']] ?? '';
                $pairs[] = $name . ':' . number_format($orig / 100, 2, '.', '');
            }
            $shares_map[(int)$tx['id']] = implode('|', $pairs);
        }
    }

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel)
    fputcsv($out, CSV_HEADERS, ',', '"', '');

    foreach ($txs as $tx) {
        $row = [
            $tx['occurred_at'],
            $tx['type'],
            $tx['payer_name'],
            number_format($tx['amount_cents'] / 100, 2, '.', ''),
            $tx['currency'],
            $ben_map[(int)$tx['id']]    ?? '',
            $shares_map[(int)$tx['id']] ?? '',
            $tx['category'],
            $tx['note'],
        ];
        fputcsv($out, $row, ',', '"', '');
    }
    fclose($out);
}

function csv_import_parse(string $file_path): array {
    $fh = fopen($file_path, 'r');
    if (!$fh) return ['error' => 'No se pudo abrir el archivo.', 'rows' => []];

    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($fh);

    $header = fgetcsv($fh, null, ',', '"', '');
    if (!$header) { fclose($fh); return ['error' => 'CSV vacío o cabecera inválida.', 'rows' => []]; }

    $header = array_map(fn($h) => strtolower(trim((string)$h)), $header);
    $required = ['date','type','payer','amount','currency'];
    foreach ($required as $col) {
        if (!in_array($col, $header, true)) {
            fclose($fh);
            return ['error' => "Falta la columna obligatoria: $col", 'rows' => []];
        }
    }

    $rows = [];
    $line = 1;
    while (($r = fgetcsv($fh, null, ',', '"', '')) !== false) {
        $line++;
        if (count(array_filter($r, fn($v) => trim((string)$v) !== '')) === 0) continue;
        $rows[] = ['line' => $line, 'data' => array_combine($header, array_pad($r, count($header), ''))];
    }
    fclose($fh);
    return ['rows' => $rows];
}

// Parse pipe-separated "Name:amount|Name:amount" → array of [name, cents]
function parse_shares_field(string $raw): array {
    $pairs = array_filter(array_map('trim', explode('|', $raw)), fn($p) => $p !== '');
    $out = [];
    foreach ($pairs as $pair) {
        $pos = strrpos($pair, ':');
        if ($pos === false) return ['error' => "Formato inválido: '$pair'. Usa 'Nombre:importe'."];
        $name = trim(substr($pair, 0, $pos));
        $amt  = trim(substr($pair, $pos + 1));
        if ($name === '' || $amt === '') return ['error' => "Par incompleto: '$pair'."];
        $cents = (int)round((float)str_replace(',', '.', $amt) * 100);
        if ($cents <= 0) return ['error' => "Importe inválido para '$name': '$amt'."];
        $out[] = ['name' => $name, 'cents' => $cents];
    }
    return $out ? ['shares' => $out] : ['error' => 'Sin repartos definidos.'];
}

function csv_import_commit(int $group_id, array $rows): array {
    $group = group_get($group_id);
    $base  = $group['base_currency'];
    $parts = participant_list($group_id);
    $pmap  = [];
    foreach ($parts as $p) $pmap[strtolower(trim($p['name']))] = (int)$p['id'];

    $valid_types = ['expense_equal','expense_shares','expense_full','gift','settlement'];
    $ok       = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $line = $entry['line'];
        $d    = $entry['data'];

        $type     = strtolower(trim((string)($d['type'] ?? '')));
        $payer_nm = strtolower(trim((string)($d['payer'] ?? '')));
        $amt_str  = trim((string)($d['amount'] ?? ''));
        $cur      = strtoupper(trim((string)($d['currency'] ?? $base)));
        $ben_nm   = strtolower(trim((string)($d['beneficiary'] ?? '')));
        $shares_s = trim((string)($d['shares'] ?? ''));
        $cat      = trim((string)($d['category'] ?? ''));
        $note     = trim((string)($d['note'] ?? ''));
        $date     = trim((string)($d['date'] ?? date('Y-m-d H:i:s')));

        if (!in_array($type, $valid_types, true)) {
            $errors[] = ['line' => $line, 'msg' => "Tipo no soportado: '$type'"];
            continue;
        }
        if (!isset($pmap[$payer_nm])) {
            $errors[] = ['line' => $line, 'msg' => "Pagador desconocido: '{$d['payer']}'"];
            continue;
        }
        $cents = (int)round((float)str_replace(',', '.', $amt_str) * 100);
        if ($cents <= 0) {
            $errors[] = ['line' => $line, 'msg' => 'Importe inválido.'];
            continue;
        }

        $data = [
            'group_id'             => $group_id,
            'type'                 => $type,
            'payer_participant_id' => $pmap[$payer_nm],
            'amount_cents'         => $cents,
            'currency'             => $cur,
            'category'             => $cat,
            'note'                 => $note,
            'occurred_at'          => $date,
        ];

        if ($type === 'expense_equal') {
            $data['participant_ids'] = array_values($pmap);
        } elseif ($type === 'expense_shares') {
            if ($shares_s === '') {
                $errors[] = ['line' => $line, 'msg' => "Columna 'shares' vacía. Formato: Alice:20|Bob:30"];
                continue;
            }
            $p = parse_shares_field($shares_s);
            if (isset($p['error'])) { $errors[] = ['line' => $line, 'msg' => $p['error']]; continue; }
            $share_rows = [];
            $sum = 0;
            $bad = false;
            foreach ($p['shares'] as $s) {
                $key = strtolower($s['name']);
                if (!isset($pmap[$key])) {
                    $errors[] = ['line' => $line, 'msg' => "Participante desconocido en shares: '{$s['name']}'"];
                    $bad = true;
                    break;
                }
                $share_rows[] = ['participant_id' => $pmap[$key], 'share_cents' => $s['cents']];
                $sum += $s['cents'];
            }
            if ($bad) continue;
            if (abs($sum - $cents) > 1) {
                $errors[] = ['line' => $line, 'msg' => sprintf(
                    'Suma de shares (%.2f) ≠ amount (%.2f)', $sum/100, $cents/100
                )];
                continue;
            }
            $data['shares'] = $share_rows;
        } elseif ($type === 'expense_full' || $type === 'settlement') {
            if (!isset($pmap[$ben_nm])) {
                $errors[] = ['line' => $line, 'msg' => "Beneficiario desconocido: '{$d['beneficiary']}'"];
                continue;
            }
            if ($pmap[$ben_nm] === $pmap[$payer_nm]) {
                $errors[] = ['line' => $line, 'msg' => 'Beneficiario y pagador son la misma persona.'];
                continue;
            }
            $data['beneficiary_id'] = $pmap[$ben_nm];
        }

        try {
            tx_create($data);
            $ok++;
        } catch (Throwable $e) {
            $errors[] = ['line' => $line, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    return ['ok' => $ok, 'errors' => $errors];
}
