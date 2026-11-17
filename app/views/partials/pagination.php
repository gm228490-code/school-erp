<?php /** @var array $p paginate() result */
if (!isset($p)) return; ?>
<div class="table-foot">
    <div><?= e(sprintf(t('Showing %d–%d of %d'), $p['from'], $p['to'], $p['total'])) ?></div>
    <?php if ($p['pages'] > 1): ?>
    <ul class="pagination pagination-sm">
        <li class="page-item <?= $p['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $p['page'] - 1])) ?>">&lsaquo;</a></li>
        <?php
        $start = max(1, $p['page'] - 2);
        $end = min($p['pages'], $p['page'] + 2);
        if ($start > 1) echo '<li class="page-item"><a class="page-link" href="' . e(url_with(['page' => 1])) . '">1</a></li>' . ($start > 2 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : '');
        for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a></li>
        <?php endfor;
        if ($end < $p['pages']) echo ($end < $p['pages'] - 1 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : '') . '<li class="page-item"><a class="page-link" href="' . e(url_with(['page' => $p['pages']])) . '">' . $p['pages'] . '</a></li>'; ?>
        <li class="page-item <?= $p['page'] >= $p['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $p['page'] + 1])) ?>">&rsaquo;</a></li>
    </ul>
    <?php endif; ?>
    <form method="get" class="d-flex align-items-center gap-1">
        <?php foreach ($_GET as $k => $v): if ($k === 'per_page' || $k === 'page' || is_array($v)) continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
        <?php endforeach; ?>
        <select name="per_page" class="form-select form-select-sm" style="width:auto" data-autosubmit>
            <?= options([10 => 10, 20 => 20, 50 => 50, 100 => 100], $p['per_page']) ?>
        </select>
    </form>
</div>
