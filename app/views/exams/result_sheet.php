<table class="table table-bordered table-sm text-center">
    <thead>
        <tr><th rowspan="2">Roll</th><th rowspan="2" class="text-start"><?= e(t('Name')) ?></th>
            <?php foreach ($subjects as $sb): ?><th><?= e($sb['name']) ?></th><?php endforeach; ?>
            <th rowspan="2"><?= e(t('Total')) ?></th><th rowspan="2">%</th><th rowspan="2"><?= e(t('Grade')) ?></th><th rowspan="2"><?= e(t('Pos')) ?></th><th rowspan="2"><?= e(t('Result')) ?></th></tr>
        <tr><?php foreach ($subjects as $sb): ?><th class="fw-normal"><?= (float)$sb['total_marks'] ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['roll_no']) ?></td><td class="text-start"><?= e(student_name($r)) ?></td>
            <?php foreach ($subjects as $sb): $m = $marks[$r['student_id']][$sb['id']] ?? null; $fail = !$m || $m['is_absent'] || (float)$m['obtained_marks'] < (float)$sb['pass_marks']; ?>
                <td style="<?= $fail ? 'color:#b91c1c;font-weight:700' : '' ?>"><?= !$m ? '-' : ($m['is_absent'] ? 'Abs' : (float)$m['obtained_marks']) ?></td>
            <?php endforeach; ?>
            <td><?= (float)$r['obtained_marks'] ?>/<?= (float)$r['total_marks'] ?></td><td><?= e($r['percentage']) ?></td><td><b><?= e($r['grade']) ?></b></td><td><?= e(ResultService::ordinal($r['position'] ? (int)$r['position'] : null)) ?></td><td><?= e(ucfirst($r['result_status'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($subjects) + 7 ?>"><?= e(t('No results generated.')) ?></td></tr><?php endif; ?>
    </tbody>
</table>
<?php if ($rows): $pass = count(array_filter($rows, fn($r) => $r['result_status'] === 'pass')); ?>
<p class="small"><?= e(t('Appeared')) ?>: <b><?= count($rows) ?></b> · <?= e(t('Passed')) ?>: <b><?= $pass ?></b> · <?= e(t('Failed')) ?>: <b><?= count($rows) - $pass ?></b> · <?= e(t('Pass %')) ?>: <b><?= round($pass / count($rows) * 100, 1) ?>%</b></p>
<?php endif; ?>
<div class="sig-row"><div><?= e(t('Class Teacher')) ?></div><div><?= e(t('Exam Controller')) ?></div><div><?= e(t('Principal')) ?></div></div>
