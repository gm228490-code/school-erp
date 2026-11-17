<?php partial('page_head', ['title' => t('Assign Students to Routes'), 'icon' => 'bi-person-up', 'crumbs' => [t('Transport') => null, t('Assign') => null],
    'actions' => '<a target="_blank" class="btn btn-sm btn-outline-secondary" href="' . e(url('reports/transport')) . '"><i class="bi bi-printer"></i> ' . e(t('Transport Report')) . '</a>']);
$routeOpts = [];
foreach ($routes as $r) $routeOpts[$r['id']] = $r['name'] . ' (' . money($r['monthly_fee']) . ')'; ?>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card mb-4"><div class="card-body">
            <form method="get" class="row g-2 align-items-end"><input type="hidden" name="r" value="transport/assign">
                <div class="col-md-4"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="tClass" class="form-select"><?= options(class_options(), $classId, t('-- Select --')) ?></select></div>
                <div class="col-md-3"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#tClass"><?= options($classId ? section_options($classId) : [], input_int('section_id'), t('All')) ?></select></div>
                <div class="col-md-3"><label class="form-label"><?= e(t('or Route')) ?></label><select name="route_id" class="form-select"><?= options($routeOpts, $routeId, t('-- Any --')) ?></select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><?= e(t('Load')) ?></button></div>
            </form>
        </div></div>
        <?php if ($students): ?>
        <form method="post" action="<?= e(url('transport/save')) ?>" class="card"><?= csrf_field() ?>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th><?= e(t('Student')) ?></th><th><?= e(t('Class')) ?></th><th><?= e(t('Address')) ?></th><th style="width:260px"><?= e(t('Route')) ?></th></tr></thead>
                <tbody><?php foreach ($students as $s): ?>
                    <tr><td><?= e(student_name($s)) ?><div class="small text-muted"><?= e($s['reg_no']) ?></div></td><td class="small"><?= e($s['class_name'] . ' ' . $s['section_name']) ?></td><td class="small text-muted"><?= e(mb_strimwidth((string)$s['address'], 0, 50, '…')) ?></td>
                        <td><select name="route[<?= (int)$s['id'] ?>]" class="form-select form-select-sm"><?= options($routeOpts, $s['route_id'], t('No transport')) ?></select></td></tr>
                <?php endforeach; ?></tbody></table></div>
            <div class="card-body border-top text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Assignments')) ?></button></div>
        </form>
        <?php elseif ($classId || $routeId): ?><div class="alert alert-light border"><?= e(t('No students found.')) ?></div><?php endif; ?>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-header"><?= e(t('Routes')) ?></div>
            <ul class="list-group list-group-flush">
            <?php foreach ($routes as $r): $pct = $r['capacity'] ? min(100, round($r['riders'] / $r['capacity'] * 100)) : 0; ?>
                <li class="list-group-item"><div class="d-flex justify-content-between"><a href="<?= e(url('transport/assign', ['route_id' => $r['id']])) ?>" class="fw-semibold"><?= e($r['name']) ?></a><span class="small"><?= (int)$r['riders'] ?>/<?= (int)$r['capacity'] ?: '-' ?></span></div>
                    <div class="small text-muted"><?= e($r['vehicle_no'] ?: t('No vehicle')) ?> · <?= e(money($r['monthly_fee'])) ?>/<?= e(t('month')) ?></div>
                    <?php if ($r['capacity']): ?><div class="progress progress-thin mt-1"><div class="progress-bar <?= $pct >= 100 ? 'bg-danger' : '' ?>" style="width:<?= $pct ?>%"></div></div><?php endif; ?></li>
            <?php endforeach; ?>
            <?php if (!$routes): ?><li class="list-group-item small text-muted"><?= e(t('No routes yet.')) ?> <a href="<?= e(url('crud', ['res' => 'routes'])) ?>"><?= e(t('Add routes')) ?></a></li><?php endif; ?>
            </ul></div>
    </div>
</div>
