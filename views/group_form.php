<?php
$editing = !empty($g['id']);
$title   = $editing ? 'Editar grupo' : 'Nuevo grupo';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><?= $title ?></h2>
        <p class="text-muted small mb-0">
            <?= $editing ? 'Modifica los datos del grupo y gestiona participantes' : 'Crea un nuevo grupo para registrar gastos compartidos' ?>
        </p>
    </div>
    <a href="?" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif ?>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-info-circle"></i> Datos del grupo</div>
    <div class="card-body">
        <form method="post" action="?action=<?= $editing ? 'group_update' : 'group_create' ?>">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $g['id'] ?>"><?php endif ?>

            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nombre del grupo</label>
                    <input type="text" class="form-control" name="name" required maxlength="120"
                           placeholder="Ej. Viaje a Estocolmo"
                           value="<?= htmlspecialchars($g['name'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Divisa base</label>
                    <select name="base_currency" class="form-select">
                        <?php foreach (CURRENCIES as $code => $name): ?>
                            <option value="<?= $code ?>" <?= ($g['base_currency'] ?? 'EUR') === $code ? 'selected' : '' ?>>
                                <?= $code ?> — <?= $name ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-brand">
                    <i class="bi <?= $editing ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                    <?= $editing ? 'Guardar cambios' : 'Crear grupo' ?>
                </button>
                <a href="?" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php if ($editing): ?>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-people"></i> Participantes (<?= count($participants) ?>)</div>
    <div class="card-body">
        <?php if (empty($participants)): ?>
            <p class="text-muted mb-3">Aún no hay participantes. Añade el primero abajo.</p>
        <?php else: ?>
        <div class="mb-3">
            <?php foreach ($participants as $p): ?>
            <div class="participant-chip">
                <i class="bi bi-person-circle"></i>
                <span><?= htmlspecialchars($p['name']) ?></span>
                <a href="?action=participant_edit&id=<?= $p['id'] ?>&g=<?= $g['id'] ?>"
                   class="btn btn-sm btn-link p-0 text-secondary" title="Editar">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <a href="?action=participant_delete&id=<?= $p['id'] ?>&g=<?= $g['id'] ?>"
                   class="btn btn-sm btn-link p-0 text-danger" title="Eliminar"
                   onclick="return confirm('¿Eliminar participante «<?= htmlspecialchars(addslashes($p['name'])) ?>»?')">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <form method="post" action="?action=group_update" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $g['id'] ?>">
            <input type="hidden" name="name" value="<?= htmlspecialchars($g['name']) ?>">
            <input type="hidden" name="base_currency" value="<?= htmlspecialchars($g['base_currency']) ?>">
            <input type="text" class="form-control" name="new_participant"
                   placeholder="Nombre del nuevo participante" maxlength="80">
            <button type="submit" name="_add_participant" value="1" class="btn btn-brand text-nowrap">
                <i class="bi bi-plus-lg"></i> Añadir
            </button>
        </form>
    </div>
</div>

<?php
$is_owner = isset($g['owner_user_id']) && (int)$g['owner_user_id'] === (int)$me_id;
?>

<?php if (!empty($members)): ?>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-shield-lock"></i> Miembros con acceso (<?= count($members) ?>)</div>
    <div class="card-body">
        <div class="mb-3">
            <?php foreach ($members as $m): ?>
            <div class="participant-chip">
                <i class="bi <?= $m['role'] === 'owner' ? 'bi-star-fill text-warning' : 'bi-person-fill' ?>"></i>
                <span>
                    <strong><?= htmlspecialchars($m['name']) ?></strong>
                    <small class="text-muted">· <?= htmlspecialchars($m['email']) ?></small>
                    <?php if ($m['role'] === 'owner'): ?>
                        <span class="badge bg-warning text-dark ms-1">propietario</span>
                    <?php endif ?>
                </span>
                <?php if ($m['role'] !== 'owner'): ?>
                <a href="?action=member_remove&id=<?= $m['id'] ?>&g=<?= $g['id'] ?>"
                   class="btn btn-sm btn-link p-0 text-danger" title="Revocar acceso"
                   onclick="return confirm('¿Revocar acceso de <?= htmlspecialchars(addslashes($m['name'])) ?>?')">
                    <i class="bi bi-x-circle"></i>
                </a>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </div>

        <form method="post" action="?action=member_add&g=<?= $g['id'] ?>" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="email" class="form-control" name="email"
                   placeholder="Email de un usuario registrado" required>
            <button type="submit" class="btn btn-brand text-nowrap">
                <i class="bi bi-person-plus"></i> Dar acceso
            </button>
        </form>
        <div class="form-text small mt-2">
            El usuario debe haberse registrado previamente en Wisenomy.
        </div>
    </div>
</div>
<?php endif ?>

<?php if ($is_owner): ?>
<?php
$other_members = array_filter($members, fn($m) => (int)$m['id'] !== (int)$me_id);
?>
<div class="card mb-4" id="invites">
    <div class="card-header"><i class="bi bi-link-45deg"></i> Invitar por enlace</div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Genera un enlace para que otros usuarios se unan al grupo sin tener que pedirles su email manualmente.
            Si aún no tienen cuenta podrán registrarse primero y se unirán automáticamente.
        </p>

        <form method="post" action="?action=invite_create&g=<?= $g['id'] ?>" class="row g-2 align-items-end mb-3">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label class="form-label small">Caduca en</label>
                <select name="ttl_days" class="form-select form-select-sm">
                    <option value="1">1 día</option>
                    <option value="7" selected>7 días</option>
                    <option value="14">14 días</option>
                    <option value="30">30 días</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Usos máximos</label>
                <select name="max_uses" class="form-select form-select-sm">
                    <option value="0" selected>Sin límite</option>
                    <option value="1">1 uso</option>
                    <option value="5">5 usos</option>
                    <option value="10">10 usos</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-brand w-100">
                    <i class="bi bi-plus-lg"></i> Crear enlace
                </button>
            </div>
        </form>

        <?php
        $active_invites  = array_filter($invites, fn($i) => invite_is_active($i));
        $expired_invites = array_filter($invites, fn($i) => !invite_is_active($i));
        ?>
        <?php if (!empty($active_invites)): ?>
            <h6 class="small text-uppercase text-muted mb-2">Enlaces activos</h6>
            <?php foreach ($active_invites as $inv):
                $url = $base_url . '?action=invite&token=' . $inv['token'];
            ?>
            <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded" style="background:#f8fafc">
                <input type="text" class="form-control form-control-sm" readonly
                       value="<?= htmlspecialchars($url) ?>" id="inv-<?= htmlspecialchars($inv['token']) ?>">
                <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap"
                        onclick="navigator.clipboard.writeText(document.getElementById('inv-<?= htmlspecialchars($inv['token']) ?>').value).then(()=>this.innerHTML='<i class=\'bi bi-check\'></i> Copiado')">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
                <form method="post" action="?action=invite_revoke&g=<?= $g['id'] ?>" class="m-0"
                      onsubmit="return confirm('¿Revocar este enlace?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($inv['token']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Revocar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </form>
            </div>
            <div class="small text-muted mb-3" style="margin-top:-4px">
                Caduca el <?= htmlspecialchars($inv['expires_at']) ?> ·
                Usos: <?= (int)$inv['uses'] ?><?= (int)$inv['max_uses'] > 0 ? ' / ' . (int)$inv['max_uses'] : '' ?>
            </div>
            <?php endforeach ?>
        <?php else: ?>
            <p class="text-muted small mb-0">No hay enlaces activos. Crea uno arriba.</p>
        <?php endif ?>

        <?php if (!empty($expired_invites)): ?>
        <details class="mt-2">
            <summary class="small text-muted" style="cursor:pointer">
                Enlaces caducados o revocados (<?= count($expired_invites) ?>)
            </summary>
            <ul class="small text-muted mt-2 mb-0">
            <?php foreach ($expired_invites as $inv): ?>
                <li>
                    <code><?= htmlspecialchars(substr($inv['token'], 0, 12)) ?>…</code>
                    · creado <?= htmlspecialchars(substr($inv['created_at'], 0, 16)) ?>
                    · <?= (int)$inv['revoked'] === 1 ? 'revocado' : 'caducado' ?>
                    · usos: <?= (int)$inv['uses'] ?>
                </li>
            <?php endforeach ?>
            </ul>
        </details>
        <?php endif ?>
    </div>
</div>

<?php if (!empty($other_members)): ?>
<div class="card mb-4 border-warning">
    <div class="card-header text-bg-warning">
        <i class="bi bi-arrow-left-right"></i> Transferir propiedad
    </div>
    <div class="card-body">
        <p class="small mb-3">
            Transfiere la propiedad de este grupo a otro miembro. Tras la transferencia,
            <strong>perderás los privilegios de propietario</strong> (no podrás eliminar el grupo, invitar miembros, etc.)
            pero seguirás siendo miembro.
        </p>
        <form method="post" action="?action=group_transfer&id=<?= $g['id'] ?>"
              onsubmit="return confirm('¿Confirmas la transferencia de propiedad? Esta acción es inmediata.');"
              class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-7">
                <label class="form-label small">Nuevo propietario</label>
                <select name="new_owner_id" class="form-select" required>
                    <option value="">— elige un miembro —</option>
                    <?php foreach ($other_members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>">
                            <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-5">
                <button type="submit" class="btn btn-warning w-100">
                    <i class="bi bi-arrow-left-right"></i> Transferir propiedad
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif ?>
<?php endif ?>

<div class="text-center">
    <a href="?g=<?= $g['id'] ?>" class="btn btn-outline-primary">
        Ir al grupo <i class="bi bi-arrow-right"></i>
    </a>
</div>
<?php endif ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
