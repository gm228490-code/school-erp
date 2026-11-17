<?php
partial('page_head', ['title' => t('Student Promotion & Year Closing'), 'icon' => 'bi-arrow-up-circle', 'crumbs' => [t('Students') => url('students'), t('Promotion') => null]]);
$cur = current_session();
?>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header"><?= e(t('1. Select students to promote')) ?></div>
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end">
                    <input type="hidden" name="r" value="students/promote">
                    <div class="col-md-3"><label class="form-label"><?= e(t('From Class')) ?></label><select name="from_class" id="pFrom" class="form-select" required><?= options(DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), $from, t('-- Select --')) ?></select></div>
                    <div class="col-md-3"><label class="form-label"><?= e(t('Section')) ?></label><select name="from_section" class="form-select" data-sections-for="#pFrom"><?= options($from ? section_options($from) : [], $fromSec, t('All Sections')) ?></select></div>
                    <div class="col-md-4"><label class="form-label"><?= e(t('Show result of exam (optional)')) ?></label><select name="exam_id" class="form-select"><?= options($exams, $examId, t('-- None --')) ?></select></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Load')) ?></button></div>
                </form>
            </div>
        </div>
        <?php if ($from): ?>
        <form method="post" action="<?= e(url('students/do_promote')) ?>" class="card" data-confirm="<?= e(t('Apply the selected action to the checked students?')) ?>">
            <?= csrf_field() ?>
            <div class="card-header d-flex justify-content-between"><span><?= e(t('2. Students')) ?> (<?= count($students) ?>)</span></div>
            <div class="table-responsive" style="max-height:460px">
                <table class="table table-sm table-hover">
                    <thead><tr><th><input type="checkbox" class="form-check-input" data-check-all=".chk-st" checked></th><th><?= e(t('Reg No')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Section')) ?></th><th><?= e(t('Roll')) ?></th><?php if ($examId): ?><th><?= e(t('Result')) ?></th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($students as $s): $pass = !$examId || ($s['result_status'] ?? '') === 'pass'; ?>
                        <tr><td><input type="checkbox" class="form-check-input chk-st" name="students[]" value="<?= (int)$s['id'] ?>" <?= $pass ? 'checked' : '' ?>></td><td><?= e($s['reg_no']) ?></td><td><?= e(student_name($s)) ?></td><td><?= e($s['section_name']) ?></td><td><?= e($s['roll_no']) ?></td>
                            <?php if ($examId): ?><td><?= $s['result_status'] ? status_badge($s['result_status']) . ' <span class="small text-muted">' . e($s['percentage']) . '%</span>' : '<span class="text-muted small">' . e(t('No result')) . '</span>' ?></td><?php endif; ?></tr>
                    <?php endforeach; ?>
                    <?php if (!$students): ?><tr><td colspan="6" class="text-muted p-3"><?= e(t('No active students in this class.')) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3"><label class="form-label"><?= e(t('Action')) ?></label>
                        <select name="action" class="form-select" id="pAction"><option value="promote"><?= e(t('Promote')) ?></option><option value="repeat"><?= e(t('Detain (repeat class)')) ?></option><option value="graduate"><?= e(t('Graduate / Pass out')) ?></option></select></div>
                    <div class="col-md-3 pTarget"><label class="form-label"><?= e(t('To Class')) ?></label><select name="to_class" id="pTo" class="form-select"><?= options(DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), $nextClass, t('-- Select --')) ?></select></div>
                    <div class="col-md-3 pTarget"><label class="form-label"><?= e(t('To Section')) ?></label><select name="to_section" class="form-select" data-sections-for="#pTo"><?= options($nextClass ? DB::pairs('SELECT id,name FROM sections WHERE class_id=?', [$nextClass]) : [], null, t('-- None --')) ?></select></div>
                    <div class="col-md-3 pTarget"><label class="form-label"><?= e(t('Session')) ?></label><select name="to_session" class="form-select"><?= options(session_options(), $cur['id'] ?? null) ?></select></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check2-all"></i> <?= e(t('Apply to selected students')) ?></button></div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <div class="col-xl-4">
        <?php if (can('academics.manage')): ?>
        <div class="card border-warning">
            <div class="card-header"><i class="bi bi-door-closed me-1"></i><?= e(t('Academic Year Closing')) ?></div>
            <div class="card-body">
                <p class="small text-muted"><?= e(t('Closes the current session, creates the new one, makes it current and copies the class-wise fee structure. Promote students first, selecting the new session as target, or after closing.')) ?></p>
                <p class="small"><?= e(t('Current session')) ?>: <b><?= e($cur['name'] ?? '-') ?></b></p>
                <?php $ny = $cur ? (int)substr($cur['end_date'], 0, 4) : (int)date('Y'); ?>
                <form method="post" action="<?= e(url('students/close_year')) ?>" data-confirm="<?= e(t('Close the current academic year? This cannot be undone.')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2"><label class="form-label"><?= e(t('New Session Name')) ?></label><input name="new_session_name" class="form-control" value="<?= $ny . '-' . ($ny + 1) ?>" required></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label"><?= e(t('Start')) ?></label><input type="date" name="new_start" class="form-control" value="<?= $cur ? e(date('Y-m-d', strtotime($cur['end_date'] . ' +1 day'))) : '' ?>" required></div>
                        <div class="col-6"><label class="form-label"><?= e(t('End')) ?></label><input type="date" name="new_end" class="form-control" value="<?= $cur ? e(date('Y-m-d', strtotime($cur['end_date'] . ' +1 year'))) : '' ?>" required></div>
                    </div>
                    <button class="btn btn-warning w-100"><i class="bi bi-door-closed"></i> <?= e(t('Close Year & Open New Session')) ?></button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $scripts = "<script>const pa=document.getElementById('pAction');if(pa){const f=()=>document.querySelectorAll('.pTarget').forEach(el=>el.style.display=pa.value==='graduate'?'none':'');pa.addEventListener('change',f);f();}</script>"; ?>
