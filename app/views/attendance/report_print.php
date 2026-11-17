<?php if (isset($daily)): ?>
<table class="table table-bordered table-sm">
    <thead><tr><th><?= e(t('Class')) ?></th><th><?= e(t('Section')) ?></th><th><?= e(t('Strength')) ?></th><th><?= e(t('Present')) ?></th><th><?= e(t('Absent')) ?></th><th><?= e(t('Leave')) ?></th><th><?= e(t('Late')) ?></th><th>%</th></tr></thead>
    <tbody><?php foreach ($daily as $r): $m = $r['p'] + $r['a'] + $r['l'] + $r['lt']; ?>
        <tr><td><?= e($r['class_name']) ?></td><td><?= e($r['section_name']) ?></td><td><?= (int)$r['strength'] ?></td><td><?= (int)$r['p'] ?></td><td><?= (int)$r['a'] ?></td><td><?= (int)$r['l'] ?></td><td><?= (int)$r['lt'] ?></td><td><?= $m ? round(($r['p'] + $r['lt']) / $m * 100, 1) . '%' : 'Not marked' ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
<?php elseif (isset($people)): include __DIR__ . '/_register.php'; endif; ?>
<div class="sig-row"><div><?= e(t('Class Teacher')) ?></div><div><?= e(t('Principal')) ?></div></div>
