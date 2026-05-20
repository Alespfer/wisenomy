<?php
$editing = !empty($tx);
$title   = $editing ? 'Editar transacción' : 'Nueva transacción';
ob_start();

$categories = ['Comida','Transporte','Alojamiento','Ocio','Compras','Salud','Servicios','Otro'];

$cur_type  = $tx['type']                 ?? 'expense_equal';
$cur_curr  = $tx['currency']             ?? $group['base_currency'];
$cur_pay   = $tx['payer_participant_id'] ?? '';
$cur_amt   = isset($tx['amount_cents'])  ? $tx['amount_cents'] / 100 : '';
$cur_occ   = $tx['occurred_at']         ?? date('Y-m-d\TH:i');
$cur_cat   = $tx['category']            ?? '';
$cur_note  = $tx['note']               ?? '';

$share_map = [];
foreach ($existing_shares ?? [] as $sh) {
    $share_map[(int)$sh['participant_id']] = $sh['share_cents'] / 100;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><?= $title ?></h2>
        <p class="text-muted small mb-0">
            Grupo: <strong><?= htmlspecialchars($group['name']) ?></strong>
            <span class="currency-pill ms-1"><?= htmlspecialchars($group['base_currency']) ?></span>
        </p>
    </div>
    <a href="?g=<?= $group['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="?action=<?= $editing ? 'tx_update' : 'tx_create' ?>" id="txform">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $tx['id'] ?>"><?php endif ?>
            <input type="hidden" name="g" value="<?= $group['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Tipo de transacción</label>
                <select name="type" id="type_sel" class="form-select" onchange="updateForm()">
                    <option value="expense_equal"  <?= $cur_type==='expense_equal'  ?'selected':'' ?>>🟦 Gasto a partes iguales</option>
                    <option value="expense_shares" <?= $cur_type==='expense_shares' ?'selected':'' ?>>🟪 Gasto con reparto personalizado</option>
                    <option value="expense_full"   <?= $cur_type==='expense_full'   ?'selected':'' ?>>🟧 Gasto completo (un beneficiario)</option>
                    <option value="gift"           <?= $cur_type==='gift'           ?'selected':'' ?>>🩷 Regalo (nadie debe nada)</option>
                    <option value="settlement"     <?= $cur_type==='settlement'     ?'selected':'' ?>>🟩 Liquidación (pago directo)</option>
                </select>
                <div class="form-text" id="type_help"></div>
            </div>

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Pagador</label>
                    <select name="payer_participant_id" class="form-select" required>
                        <option value="">— selecciona —</option>
                        <?php foreach ($participants as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (string)$cur_pay===(string)$p['id']?'selected':'' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Importe</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="form-control"
                           value="<?= htmlspecialchars((string)$cur_amt) ?>" placeholder="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Divisa</label>
                    <select name="currency" class="form-select">
                        <?php foreach (CURRENCIES as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $cur_curr===$code?'selected':'' ?> title="<?= $name ?>"><?= $code ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <!-- expense_equal -->
            <div id="sec_equal" class="mt-3 p-3" style="background:#f8fafc;border-radius:8px">
                <label class="form-label mb-2">
                    <i class="bi bi-people"></i> Participantes (reparto igual)
                </label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($participants as $p): ?>
                        <?php $checked = empty($existing_shares) || in_array($p['id'], array_column($existing_shares??[], 'participant_id')); ?>
                        <div class="form-check participant-chip">
                            <input class="form-check-input" type="checkbox" name="participant_ids[]"
                                   value="<?= $p['id'] ?>" id="pe_<?= $p['id'] ?>"
                                   <?= $checked ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pe_<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['name']) ?>
                            </label>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <!-- expense_shares -->
            <div id="sec_shares" class="mt-3 p-3" style="background:#f8fafc;border-radius:8px">
                <label class="form-label mb-2">
                    <i class="bi bi-pie-chart"></i> Reparto personalizado
                </label>
                <div class="text-muted small mb-2">Indica cuánto debe cada participante (en la divisa seleccionada arriba).</div>
                <?php foreach ($participants as $p): ?>
                <div class="share-input-row">
                    <label><i class="bi bi-person-circle text-muted"></i> <?= htmlspecialchars($p['name']) ?></label>
                    <input type="number" name="shares[<?= $p['id'] ?>]" step="0.01" min="0"
                           class="form-control" placeholder="0.00"
                           value="<?= htmlspecialchars((string)($share_map[$p['id']] ?? '')) ?>">
                </div>
                <?php endforeach ?>
                <div id="shares_warn" class="alert alert-warning py-2 mt-2 mb-0 small d-none"></div>
            </div>

            <!-- expense_full / settlement -->
            <div id="sec_beneficiary" class="mt-3">
                <label class="form-label" id="ben_label">Beneficiario</label>
                <select name="beneficiary_id" class="form-select">
                    <option value="">— selecciona —</option>
                    <?php
                    $cur_ben = '';
                    if ($editing && in_array($tx['type'], ['expense_full','settlement'])) {
                        $sh0 = $existing_shares[0] ?? null;
                        $cur_ben = $sh0 ? $sh0['participant_id'] : '';
                    }
                    ?>
                    <?php foreach ($participants as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (string)$cur_ben===(string)$p['id']?'selected':'' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Categoría</label>
                    <select name="category" class="form-select">
                        <option value="">—</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $cur_cat===$cat?'selected':'' ?>><?= $cat ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Fecha y hora</label>
                    <input type="datetime-local" name="occurred_at" class="form-control"
                           value="<?= htmlspecialchars(str_replace(' ','T',substr($cur_occ,0,16))) ?>">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Nota (opcional)</label>
                <textarea name="note" class="form-control" rows="2"
                          placeholder="Descripción breve..."><?= htmlspecialchars($cur_note) ?></textarea>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-brand">
                    <i class="bi <?= $editing ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                    <?= $editing ? 'Guardar cambios' : 'Crear transacción' ?>
                </button>
                <a href="?g=<?= $group['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
const TYPE_HELP = {
    expense_equal:  'El pagador adelanta el importe y se reparte a partes iguales entre los participantes marcados.',
    expense_shares: 'Reparto personalizado: define cuánto debe cada uno. La suma debe coincidir con el importe.',
    expense_full:   'Un único beneficiario debe el importe completo al pagador.',
    gift:           'El pagador asume el gasto. Nadie debe nada — no afecta a los balances.',
    settlement:     'Pago directo del pagador al receptor para liquidar deuda existente.',
};
function updateForm() {
    const t = document.getElementById('type_sel').value;
    document.getElementById('sec_equal').style.display       = t === 'expense_equal'  ? '' : 'none';
    document.getElementById('sec_shares').style.display      = t === 'expense_shares' ? '' : 'none';
    document.getElementById('sec_beneficiary').style.display = (t === 'expense_full' || t === 'settlement') ? '' : 'none';
    document.querySelector('#sec_beneficiary label').textContent = t === 'settlement' ? 'Receptor del pago' : 'Beneficiario';
    document.getElementById('type_help').textContent = TYPE_HELP[t] || '';
}
updateForm();

document.getElementById('txform').addEventListener('input', function() {
    if (document.getElementById('type_sel').value !== 'expense_shares') return;
    const inputs = document.querySelectorAll('[name^="shares["]');
    let sum = 0;
    inputs.forEach(i => { sum += parseFloat(i.value||0); });
    const amt = parseFloat(document.querySelector('[name=amount]').value||0);
    const warn = document.getElementById('shares_warn');
    if (amt > 0 && Math.abs(sum - amt) > 0.01) {
        warn.textContent = `Suma de repartos: ${sum.toFixed(2)} ≠ Importe: ${amt.toFixed(2)} (diferencia: ${(sum-amt).toFixed(2)})`;
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
