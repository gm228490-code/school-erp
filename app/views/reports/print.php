<table class="table table-bordered table-sm">
    <thead><tr><th>#</th><?php foreach ($headers as $h): ?><th><?= e(t($h)) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?><tr><td><?= $i + 1 ?></td><?php foreach ($r as $c): ?><td><?= e($c) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($headers) + 1 ?>" class="text-center"><?= e(t('No records found.')) ?></td></tr><?php endif; ?>
    </tbody>
    <?php if ($footer && $rows): ?><tfoot><tr><th></th><?php foreach ($footer as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></tfoot><?php endif; ?>
</table>
<div class="sig-row"><div><?= e(t('Prepared by')) ?></div><div><?= e(t('Principal')) ?></div></div>
