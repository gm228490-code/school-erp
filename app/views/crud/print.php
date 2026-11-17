<?php /** @var Crud $c @var array $rows */
$cols = array_filter($c->d['columns'], fn($col) => ($col['type'] ?? '') !== 'image' && empty($col['noexport'])); ?>
<table class="table table-bordered table-sm">
    <thead><tr><th>#</th><?php foreach ($cols as $col): ?><th><?= e(t($col[0])) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
        <tr><td><?= $i + 1 ?></td><?php foreach ($cols as $k => $_): ?><td><?= e($c->cell($k, $r, true)) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($cols) + 1 ?>" class="text-center"><?= e(t('No records found.')) ?></td></tr><?php endif; ?>
    </tbody>
</table>
<div class="small">Total records: <?= count($rows) ?></div>
