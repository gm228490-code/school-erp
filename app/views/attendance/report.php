<?php
partial('page_head', ['title' => t('Attendance Reports'), 'icon' => 'bi-clipboard-data', 'crumbs' => [t('Attendance') => null, t('Reports') => null]]);
$q = $_GET;
?>
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $mode === 'monthly' ? 'active' : '' ?>" href="<?= e(url('attendance/report', ['mode' => 'monthly'])) ?>"><?= e(t('Monthly Register (Students)')) ?></a></li>
    <li class="nav-item"><a class="nav-link <?= $mode === 'daily' ? 'active' : '' ?>" href="<?= e(url('attendance/report', ['mode' => 'daily'])) ?>"><?= e(t('Daily Summary')) ?></a></li>
    <?php if (can('attendance.employee') || can('leaves.view')): ?><li class="nav-item"><a class="nav-link <?= $mode === 'employees' ? 'active' : '' ?>" href="<?= e(url('attendance/report', ['mode' => 'employees'])) ?>"><?= e(t('Teachers / Staff')) ?></a></li><?php endif; ?>
</ul>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="attendance/report"><input type="hidden" name="mode" value="<?= e($mode) ?>">
        <?php if ($mode === 'daily'): ?>
            <div class="col-md-4"><label class="form-label"><?= e(t('Date')) ?></label><input type="date" name="date" value="<?= e($date) ?>" class="form-control"></div>
        <?php elseif ($mode === 'employees'): ?>
            <div class="col-md-4"><label class="form-label"><?= e(t('Type')) ?></label><select name="type" class="form-select"><?= options(['teacher' => t('Teachers'), 'staff' => t('Staff')], $type) ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= e(t('Month')) ?></label><input type="month" name="month" value="<?= e($month) ?>" class="form-control"></div>
        <?php else: ?>
            <div class="col-md-3"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="rClass" class="form-select" required><?= options(class_options(), $classId, t('-- Select --')) ?></select></div>
            <div class="col-md-3"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#rClass"><?= options($classId ? section_options($classId) : [], $sectionId, t('All Sections')) ?></select></div>
            <div class="col-md-3"><label class="form-label"><?= e(t('Month')) ?></label><input type="month" name="month" value="<?= e($month) ?>" class="form-control"></div>
        <?php endif; ?>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Show')) ?></button></div>
    </form>
</div></div>

<?php if (isset($daily) || isset($people)): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= e(t('Report')) ?></span>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(url_with(['print' => 1])) ?>"><i class="bi bi-printer"></i> <?= e(t('Print / PDF')) ?></a>
            <a class="btn btn-sm btn-outline-success" href="<?= e(url_with(['export' => 'xls'])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url_with(['export' => 'csv'])) ?>">CSV</a>
        </div>
    </div>
    <div class="card-body table-responsive">
        <?php if (isset($daily)): ?>
            <table class="table table-sm table-hover">
                <thead><tr><th><?= e(t('Class')) ?></th><th><?= e(t('Section')) ?></th><th><?= e(t('Strength')) ?></th><th><?= e(t('Present')) ?></th><th><?= e(t('Absent')) ?></th><th><?= e(t('Leave')) ?></th><th><?= e(t('Late')) ?></th><th>%</th><th></th></tr></thead>
                <tbody>
                <?php $T = ['s' => 0, 'p' => 0, 'a' => 0, 'l' => 0, 'lt' => 0];
                foreach ($daily as $r): $m = $r['p'] + $r['a'] + $r['l'] + $r['lt']; foreach (['s' => 'strength', 'p' => 'p', 'a' => 'a', 'l' => 'l', 'lt' => 'lt'] as $k => $f) $T[$k] += (int)$r[$f]; ?>
                    <tr><td><?= e($r['class_name']) ?></td><td><?= e($r['section_name']) ?></td><td><?= (int)$r['strength'] ?></td><td class="text-success fw-semibold"><?= (int)$r['p'] ?></td><td class="text-danger fw-semibold"><?= (int)$r['a'] ?></td><td><?= (int)$r['l'] ?></td><td><?= (int)$r['lt'] ?></td>
                        <td><?= $m ? round(($r['p'] + $r['lt']) / $m * 100, 1) . '%' : '<span class="badge text-bg-warning badge-soft">' . e(t('Not marked')) . '</span>' ?></td>
                        <td><?php if (can('attendance.mark')): ?><a class="btn btn-sm btn-light-soft" href="<?= e(url('attendance/students', ['class_id' => $r['class_id'], 'section_id' => $r['section_id'], 'date' => $date])) ?>"><i class="bi bi-pencil"></i></a><?php endif; ?></td></tr>
                <?php endforeach; ?>
                <tr class="fw-bold"><td colspan="2"><?= e(t('Total')) ?></td><td><?= $T['s'] ?></td><td><?= $T['p'] ?></td><td><?= $T['a'] ?></td><td><?= $T['l'] ?></td><td><?= $T['lt'] ?></td><td><?= ($T['p'] + $T['a'] + $T['l'] + $T['lt']) ? round(($T['p'] + $T['lt']) / ($T['p'] + $T['a'] + $T['l'] + $T['lt']) * 100, 1) . '%' : '-' ?></td><td></td></tr>
                </tbody>
            </table>
        <?php else: include __DIR__ . '/_register.php'; endif; ?>
    </div>
</div>
<?php endif; ?>
