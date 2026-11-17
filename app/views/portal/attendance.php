<?php $icon = 'bi-calendar-check'; include __DIR__ . '/_head.php';
$first = strtotime($month . '-01'); $days = (int)date('t', $first); $offset = (int)date('N', $first) - 1;
$cls = ['present' => 'success', 'absent' => 'danger', 'leave' => 'info', 'late' => 'warning']; ?>
<div class="row g-4">
    <div class="col-lg-7"><div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <a class="btn btn-sm btn-light-soft" href="<?= e(url('portal/attendance', ['month' => date('Y-m', strtotime('-1 month', $first))])) ?>"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-semibold"><?= e(fmt_month($month)) ?></span>
            <a class="btn btn-sm btn-light-soft" href="<?= e(url('portal/attendance', ['month' => date('Y-m', strtotime('+1 month', $first))])) ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="card-body">
            <div class="d-grid text-center small" style="grid-template-columns:repeat(7,1fr);gap:6px">
                <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d): ?><div class="text-muted fw-semibold"><?= e(t($d)) ?></div><?php endforeach; ?>
                <?php for ($i = 0; $i < $offset; $i++): ?><div></div><?php endfor; ?>
                <?php for ($d = 1; $d <= $days; $d++): $st = $rows[$d] ?? null; ?>
                    <div class="rounded py-2 <?= $st ? 'text-bg-' . $cls[$st] . ' badge-soft' : 'border' ?>" title="<?= e($st ? t(ucfirst($st)) : '') ?>"><?= $d ?><?= $st ? '<div style="font-size:.65rem">' . e(t(ucfirst($st))) . '</div>' : '' ?></div>
                <?php endfor; ?>
            </div>
        </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header"><?= e(t('Monthly Summary')) ?></div>
        <table class="table table-sm mb-0"><thead><tr><th><?= e(t('Month')) ?></th><th>P</th><th>A</th><th>L</th><th>LT</th><th>%</th></tr></thead><tbody>
        <?php foreach ($summary as $m): $t = $m['p'] + $m['a'] + $m['l'] + $m['lt']; ?><tr><td><?= e(fmt_month($m['m'])) ?></td><td><?= (int)$m['p'] ?></td><td class="text-danger"><?= (int)$m['a'] ?></td><td><?= (int)$m['l'] ?></td><td><?= (int)$m['lt'] ?></td><td class="fw-semibold"><?= $t ? round(($m['p'] + $m['lt']) / $t * 100) : 0 ?>%</td></tr><?php endforeach; ?>
        <?php if (!$summary): ?><tr><td colspan="6" class="text-muted"><?= e(t('No attendance recorded yet.')) ?></td></tr><?php endif; ?>
        </tbody></table></div></div>
</div>
