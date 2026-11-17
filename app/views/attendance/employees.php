<?php
partial('page_head', ['title' => t('Employee Attendance'), 'icon' => 'bi-person-check', 'crumbs' => [t('Attendance') => null, t('Employees') => null]]);
$codeOf = ['present' => 'P', 'absent' => 'A', 'leave' => 'L', 'late' => 'LT'];
?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="attendance/employees">
        <div class="col-md-4"><label class="form-label"><?= e(t('Employee Type')) ?></label><select name="type" class="form-select"><?= options(['teacher' => t('Teachers'), 'staff' => t('Staff')], $type) ?></select></div>
        <div class="col-md-4"><label class="form-label"><?= e(t('Date')) ?></label><input type="date" name="date" class="form-control" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Load')) ?></button></div>
    </form>
</div></div>
<form method="post" action="<?= e(url('attendance/save_employees')) ?>" class="card">
    <?= csrf_field() ?><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="date" value="<?= e($date) ?>">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= e(fmt_date($date, 'l, d F Y')) ?> · <?= count($rows) ?></span>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="document.querySelectorAll('input.att-P').forEach(i=>i.checked=true)"><?= e(t('All Present')) ?></button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th><?= e(t('Employee')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Check In')) ?></th><th><?= e(t('Check Out')) ?></th><th><?= e(t('Remarks')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $cur = $r['status'] ? $codeOf[$r['status']] : 'P'; ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($r['name']) ?></div><div class="small text-muted"><?= e($r['employee_no'] . ' · ' . $r['desig']) ?></div></td>
                    <td><div class="btn-group att-group">
                        <?php foreach (['P' => 'success', 'A' => 'danger', 'L' => 'info', 'LT' => 'warning'] as $c => $cls): $iid = "e{$r['id']}$c"; ?>
                            <input type="radio" class="btn-check att-<?= $c ?>" name="att[<?= (int)$r['id'] ?>]" id="<?= $iid ?>" value="<?= $c ?>" <?= $cur === $c ? 'checked' : '' ?>><label class="btn btn-outline-<?= $cls ?>" for="<?= $iid ?>"><?= $c ?></label>
                        <?php endforeach; ?></div></td>
                    <td><input type="time" class="form-control form-control-sm" name="check_in[<?= (int)$r['id'] ?>]" value="<?= e($r['check_in'] ? substr($r['check_in'], 0, 5) : '') ?>"></td>
                    <td><input type="time" class="form-control form-control-sm" name="check_out[<?= (int)$r['id'] ?>]" value="<?= e($r['check_out'] ? substr($r['check_out'], 0, 5) : '') ?>"></td>
                    <td><input type="text" class="form-control form-control-sm" name="remarks[<?= (int)$r['id'] ?>]" value="<?= e($r['remarks']) ?>" maxlength="255"></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5"><div class="empty-state"><?= e(t('No active employees.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($rows): ?><div class="card-body border-top text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Attendance')) ?></button></div><?php endif; ?>
</form>
