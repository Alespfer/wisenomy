<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Returns ['balances' => [participant_id => ['name'=>, 'cents'=>]], 'payments' => [['from'=>name,'to'=>name,'cents'=>int]]]
function compute_settlement(int $group_id): array {
    $db = db();

    $parts = $db->prepare('SELECT id, name FROM participants WHERE group_id=?');
    $parts->execute([$group_id]);
    $names = [];
    $bal   = [];
    foreach ($parts->fetchAll() as $p) {
        $names[(int)$p['id']] = $p['name'];
        $bal[(int)$p['id']]   = 0;
    }

    $txs = $db->prepare(
        'SELECT id, type, payer_participant_id, amount_base_cents FROM transactions WHERE group_id=?'
    );
    $txs->execute([$group_id]);

    $sh_stmt = $db->prepare('SELECT participant_id, share_cents FROM transaction_shares WHERE transaction_id=?');

    foreach ($txs->fetchAll() as $tx) {
        $type  = $tx['type'];
        $payer = (int)$tx['payer_participant_id'];
        $amt   = (int)$tx['amount_base_cents'];
        $tx_id = (int)$tx['id'];

        if ($type === 'gift') continue;

        if ($type === 'settlement') {
            // payer reduces debt of beneficiary; payer gets credit
            $sh_stmt->execute([$tx_id]);
            $shares = $sh_stmt->fetchAll();
            foreach ($shares as $sh) {
                $ben = (int)$sh['participant_id'];
                $bal[$payer] = ($bal[$payer] ?? 0) + (int)$sh['share_cents']; // payer paid → credit
                $bal[$ben]   = ($bal[$ben]   ?? 0) - (int)$sh['share_cents']; // ben received payment → debt reduced
            }
            continue;
        }

        // expense_*: payer advanced money → credit
        if (isset($bal[$payer])) {
            $bal[$payer] += $amt;
        }

        // each share holder owes their share
        $sh_stmt->execute([$tx_id]);
        foreach ($sh_stmt->fetchAll() as $sh) {
            $pid = (int)$sh['participant_id'];
            if (isset($bal[$pid])) {
                $bal[$pid] -= (int)$sh['share_cents'];
            }
        }
    }

    // Rounding correction: assign residual cent to participant with largest absolute balance
    $total = array_sum($bal);
    if ($total !== 0 && count($bal) > 0) {
        $pivot = array_keys($bal)[0];
        foreach ($bal as $pid => $v) {
            if (abs($v) > abs($bal[$pivot])) $pivot = $pid;
        }
        $bal[$pivot] -= $total;
    }

    assert(array_sum($bal) === 0, 'Balance conservation violated');

    $payments = minimize_payments($bal, $names);

    $balances = [];
    foreach ($bal as $pid => $cents) {
        $balances[$pid] = ['name' => $names[$pid] ?? "#{$pid}", 'cents' => $cents];
    }

    return ['balances' => $balances, 'payments' => $payments];
}

function minimize_payments(array $bal, array $names): array {
    $debtors   = [];
    $creditors = [];
    foreach ($bal as $pid => $cents) {
        if ($cents < 0) $debtors[$pid]   = -$cents; // owe this much
        if ($cents > 0) $creditors[$pid] = $cents;  // are owed this much
    }

    $payments = [];
    while (!empty($debtors) && !empty($creditors)) {
        // largest debtor and largest creditor
        arsort($debtors);
        arsort($creditors);
        $d_pid = array_key_first($debtors);
        $c_pid = array_key_first($creditors);

        $amount = min($debtors[$d_pid], $creditors[$c_pid]);
        $payments[] = [
            'from'  => $names[$d_pid]  ?? "#{$d_pid}",
            'to'    => $names[$c_pid]  ?? "#{$c_pid}",
            'cents' => $amount,
        ];

        $debtors[$d_pid]   -= $amount;
        $creditors[$c_pid] -= $amount;

        if ($debtors[$d_pid]   === 0) unset($debtors[$d_pid]);
        if ($creditors[$c_pid] === 0) unset($creditors[$c_pid]);
    }

    return $payments;
}
