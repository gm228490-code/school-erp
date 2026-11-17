<?php
/** Monthly register grid: $people, $grid, $days, $month */
$codes = ['present' => 'P', 'absent' => 'A', 'leave' => 'L', 'late' => 'LT'];
$wd = school_days();
?>
<table class="table table-bordered table-sm reg-table">
    <thead><tr><th>#</th><th class="text-start"><?= e(t('Name')) ?></th>
        <?php for ($i = 1; $i <= $days; $i++): $dow = (int)date('N', strtotime("$month-" . str_pad((string)$i, 2, '0', STR_PAD_LEFT))); ?>
            <th class="<?= in_array($dow, $wd, true) ? '' : 'text-danger' ?>"><?= $i ?></th>
        <?php endfor; ?>
        <th>P</th><th>A</th><th>L</th><th>LT</th><th>%</th></tr></thead>
    <tbody>
    <?php foreach ($people as $n => $p): $g = $grid[$p['id']] ?? []; $c = array_count_values($g); $tot = count($g); ?>
        <tr><td><?= e($p['roll_no'] ?? ($n + 1)) ?></td><td class="text-start text-nowrap"><?= e($p['name'] ?? student_name($p)) ?></td>
            <?php for ($i = 1; $i <= $days; $i++): $st = $g[$i] ?? null; ?><td class="<?= $st ? 'reg-' . $codes[$st] : '' ?>"><?= $st ? $codes[$st] : '' ?></td><?php endfor; ?>
            <td><?= $c['present'] ?? 0 ?></td><td><?= $c['absent'] ?? 0 ?></td><td><?= $c['leave'] ?? 0 ?></td><td><?= $c['late'] ?? 0 ?></td>
            <td class="fw-bold"><?= $tot ? round((($c['present'] ?? 0) + ($c['late'] ?? 0)) / $tot * 100) : '-' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$people): ?><tr><td colspan="<?= $days + 7 ?>"><?= e(t('No records.')) ?></td></tr><?php endif; ?>
    </tbody>
</table>
<div class="small text-muted">P = <?= e(t('Present')) ?>, A = <?= e(t('Absent')) ?>, L = <?= e(t('Leave')) ?>, LT = <?= e(t('Late')) ?>. % = (P + LT) / <?= e(t('days marked')) ?>.</div>
