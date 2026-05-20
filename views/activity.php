<?php
$title = 'Actividad — ' . $group['name'];
ob_start();

$action_labels = [
    'group_create'        => ['bi-folder-plus',    'primary'],
    'group_update'        => ['bi-pencil-square',  'secondary'],
    'ownership_transfer'  => ['bi-arrow-left-right','warning'],
    'member_add'          => ['bi-person-plus',    'success'],
    'member_remove'       => ['bi-person-x',       'danger'],
    'member_join'         => ['bi-person-check',   'success'],
    'invite_create'       => ['bi-link-45deg',     'info'],
    'invite_revoke'       => ['bi-link',           'secondary'],
    'participant_create'  => ['bi-person-add',     'primary'],
    'participant_update'  => ['bi-pencil',         'secondary'],
    'participant_delete'  => ['bi-person-dash',    'danger'],
    'tx_create'           => ['bi-plus-circle',    'success'],
    'tx_update'           => ['bi-pencil',         'secondary'],
    'tx_delete'           => ['bi-trash',          'danger'],
];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-clock-history"></i> Actividad</h2>
        <p class="text-muted small mb-0">
            Historial de cambios en <strong><?= htmlspecialchars($group['name']) ?></strong>
        </p>
    </div>
    <a href="?g=<?= $group['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al grupo
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
    <?php if (empty($entries)): ?>
        <div class="empty-state">
            <i class="bi bi-clock"></i>
            <h6>Sin actividad registrada</h6>
            <p class="mb-0">Las acciones sobre el grupo aparecerán aquí en cuanto sucedan.</p>
        </div>
    <?php else: ?>
        <ul class="list-group list-group-flush">
        <?php foreach ($entries as $e):
            [$icon, $color] = $action_labels[$e['action']] ?? ['bi-circle', 'secondary'];
            $when = htmlspecialchars(substr($e['created_at'], 0, 16));
            $who  = $e['user_name'] !== '' ? $e['user_name'] : '(usuario eliminado)';
        ?>
            <li class="list-group-item d-flex align-items-start gap-3">
                <span class="badge rounded-pill text-bg-<?= $color ?>" style="min-width:32px; padding:8px;">
                    <i class="bi <?= $icon ?>"></i>
                </span>
                <div class="flex-grow-1">
                    <div><?= htmlspecialchars($e['summary']) ?></div>
                    <div class="small text-muted">
                        <i class="bi bi-person"></i> <?= htmlspecialchars($who) ?>
                        · <i class="bi bi-clock"></i> <?= $when ?>
                    </div>
                </div>
            </li>
        <?php endforeach ?>
        </ul>
    <?php endif ?>
    </div>
</div>

<?php if ($pages > 1):
    $first = ($page - 1) * $per_page + 1;
    $last  = min($total, $page * $per_page);
    function pg_url(int $gid, int $p): string {
        return '?action=activity&g=' . $gid . '&page=' . $p;
    }
?>
<nav class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="small text-muted">
        Mostrando <?= $first ?>–<?= $last ?> de <?= $total ?> acciones
    </span>
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= pg_url($group['id'], max(1, $page - 1)) ?>">&laquo;</a>
        </li>
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= pg_url($group['id'], 1) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?>
        <?php endif ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= pg_url($group['id'], $p) ?>"><?= $p ?></a>
            </li>
        <?php endfor ?>
        <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?>
            <li class="page-item"><a class="page-link" href="<?= pg_url($group['id'], $pages) ?>"><?= $pages ?></a></li>
        <?php endif ?>
        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= pg_url($group['id'], min($pages, $page + 1)) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php else: ?>
<p class="small text-muted text-center mt-3">
    <?= $total ?> acción<?= $total === 1 ? '' : 'es' ?> registrada<?= $total === 1 ? '' : 's' ?>.
</p>
<?php endif ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
