<?php
partial('page_head', ['title' => t('Student Attendance'), 'icon' => 'bi-check2-square', 'crumbs' => [t('Attendance') => null, t('Students') => null]]);
$codeOf = ['present' => 'P', 'absent' => 'A', 'leave' => 'L', 'late' => 'LT'];
?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="attendance/students">
        <div class="col-md-3"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="aClass" class="form-select" required><?= options(class_options(), $classId, t('-- Select --')) ?></select></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#aClass"><?= options($classId ? section_options($classId) : [], $sectionId, t('All Sections')) ?></select></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('Date')) ?></label><input type="date" name="date" class="form-control" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Load Students')) ?></button></div>
    </form>
</div></div>

<?php if ($holiday): ?><div class="alert alert-warning"><i class="bi bi-sun me-1"></i><?= e(t('Holiday')) ?>: <?= e($holiday) ?></div><?php endif; ?>
<?php if (!in_array((int)date('N', strtotime($date)), school_days(), true)): ?><div class="alert alert-warning"><i class="bi bi-calendar-x me-1"></i><?= e(t('The selected date is not a working day.')) ?></div><?php endif; ?>

<?php if ($classId): ?>
<form method="post" action="<?= e(url('attendance/save_students')) ?>" class="card">
    <?= csrf_field() ?>
    <input type="hidden" name="class_id" value="<?= (int)$classId ?>"><input type="hidden" name="section_id" value="<?= (int)$sectionId ?>"><input type="hidden" name="date" value="<?= e($date) ?>">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span><?= e(fmt_date($date, 'l, d F Y')) ?> · <?= count($students) ?> <?= e(t('students')) ?> <?= $existing ? '<span class="badge text-bg-success badge-soft ms-1">' . e(t('Already marked — editing')) . '</span>' : '' ?></span>
        <div class="btn-group btn-group-sm">
            <?php foreach (['P' => ['success', 'All Present'], 'A' => ['danger', 'All Absent'], 'L' => ['info', 'All Leave']] as $c => [$cls, $lbl]): ?>
                <button type="button" class="btn btn-outline-<?= $cls ?>" onclick="document.querySelectorAll('input.att-<?= $c ?>').forEach(i=>i.checked=true);countAtt()"><?= e(t($lbl)) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th><?= e(t('Roll')) ?></th><th><?= e(t('Student')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Remarks')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($students as $s):
                $cur = isset($existing[$s['id']]) ? $codeOf[$existing[$s['id']]['status']] : 'P'; ?>
                <tr>
                    <td class="fw-semibold"><?= e($s['roll_no']) ?></td>
                    <td><div class="d-flex align-items-center gap-2"><?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'])) ?>" class="avatar-sm" alt=""><?php else: ?><span class="avatar-sm avatar-ph"><?= e(mb_substr($s['first_name'], 0, 1)) ?></span><?php endif; ?>
                        <div><div class="fw-semibold"><?= e(student_name($s)) ?></div><div class="small text-muted"><?= e($s['reg_no']) ?></div></div></div></td>
                    <td><div class="btn-group att-group" role="group">
                        <?php foreach (['P' => 'success', 'A' => 'danger', 'L' => 'info', 'LT' => 'warning'] as $c => $cls): $iid = "a{$s['id']}$c"; ?>
                            <input type="radio" class="btn-check att-<?= $c ?>" name="att[<?= (int)$s['id'] ?>]" id="<?= $iid ?>" value="<?= $c ?>" <?= $cur === $c ? 'checked' : '' ?> onchange="countAtt()">
                            <label class="btn btn-outline-<?= $cls ?>" for="<?= $iid ?>" title="<?= e(t(ucfirst(['P' => 'present', 'A' => 'absent', 'L' => 'leave', 'LT' => 'late'][$c]))) ?>"><?= $c ?></label>
                        <?php endforeach; ?>
                    </div></td>
                    <td><input type="text" name="remarks[<?= (int)$s['id'] ?>]" class="form-control form-control-sm" maxlength="255" value="<?= e($existing[$s['id']]['remarks'] ?? '') ?>"></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="4"><div class="empty-state"><i class="bi bi-people"></i><?= e(t('No active students in this class/section.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($students): ?>
    <div class="card-body border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div id="attCount" class="small fw-semibold"></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Attendance')) ?></button>
    </div>
    <?php endif; ?>
</form>
<?php endif; ?>
<?php $scripts = "<script>function countAtt(){const c={P:0,A:0,L:0,LT:0};document.querySelectorAll('.att-group input:checked').forEach(i=>c[i.value]++);const el=document.getElementById('attCount');if(el)el.textContent='P: '+c.P+'  ·  A: '+c.A+'  ·  L: '+c.L+'  ·  LT: '+c.LT;}countAtt();</script>"; ?>
