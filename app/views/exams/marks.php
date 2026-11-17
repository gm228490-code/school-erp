<?php partial('page_head', ['title' => t('Marks Entry'), 'icon' => 'bi-123', 'crumbs' => [t('Examinations') => url('crud', ['res' => 'exams']), t('Marks Entry') => null]]); ?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="exams/marks">
        <div class="col-md-3"><label class="form-label"><?= e(t('Exam')) ?></label><select name="exam_id" class="form-select" required data-autosubmit><?= options($exams, $examId, t('-- Select --')) ?></select></div>
        <div class="col-md-2"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="mClass" class="form-select" required data-autosubmit><?= options(class_options(), $classId, t('-- Select --')) ?></select></div>
        <div class="col-md-2"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#mClass"><?= options($classId ? section_options($classId) : [], $sectionId, Auth::scoped() ? t('-- Select --') : t('All Sections')) ?></select></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('Subject')) ?></label><select name="subject_id" class="form-select" required><?= options($subjects, $subjectId, t('-- Select --')) ?></select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Load')) ?></button></div>
    </form>
    <?php if ($classId && $examId && !$subjects): ?><div class="alert alert-warning mt-3 mb-0 small"><?= e(t('No subjects scheduled for this class. Set up the Exam Schedule first.')) ?></div><?php endif; ?>
</div></div>
<?php if ($es): ?>
<form method="post" action="<?= e(url('exams/save_marks')) ?>" class="card">
    <?= csrf_field() ?><input type="hidden" name="exam_subject_id" value="<?= (int)$es['id'] ?>"><input type="hidden" name="section_id" value="<?= (int)$sectionId ?>">
    <div class="card-header d-flex justify-content-between flex-wrap gap-2">
        <span><b><?= e($es['subject']) ?></b> · <?= e(t('Total')) ?>: <?= (float)$es['total_marks'] ?> · <?= e(t('Pass')) ?>: <?= (float)$es['pass_marks'] ?></span>
        <?php if ($es['is_published']): ?><span class="badge text-bg-warning badge-soft"><?= e(t('Results published')) ?></span><?php endif; ?>
    </div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th><?= e(t('Roll')) ?></th><th><?= e(t('Student')) ?></th><th style="width:150px"><?= e(t('Marks')) ?></th><th><?= e(t('Absent')) ?></th><th><?= e(t('Remarks')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($students as $s): $m = $marks[$s['id']] ?? null; ?>
            <tr><td><?= e($s['roll_no']) ?></td><td><?= e(student_name($s)) ?> <span class="small text-muted"><?= e($s['reg_no']) ?></span></td>
                <td><input type="number" step="0.25" min="0" max="<?= (float)$es['total_marks'] ?>" class="form-control form-control-sm mark-in" name="marks[<?= (int)$s['id'] ?>]" value="<?= $m && !$m['is_absent'] ? e((float)$m['obtained_marks']) : '' ?>" data-pass="<?= (float)$es['pass_marks'] ?>"></td>
                <td><input type="checkbox" class="form-check-input" name="absent[<?= (int)$s['id'] ?>]" value="1" <?= $m && $m['is_absent'] ? 'checked' : '' ?>></td>
                <td><input class="form-control form-control-sm" name="remarks[<?= (int)$s['id'] ?>]" maxlength="100" value="<?= e($m['remarks'] ?? '') ?>"></td></tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="5"><div class="empty-state"><?= e(t('No students.')) ?></div></td></tr><?php endif; ?>
        </tbody></table></div>
    <div class="card-body border-top d-flex justify-content-between"><span class="small text-muted"><?= e(t('Leave blank if not yet entered. Marks below pass marks are highlighted.')) ?></span><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Marks')) ?></button></div>
</form>
<?php elseif ($examId && $classId && $subjectId): ?><div class="alert alert-warning"><?= e(t('This subject is not scheduled in the selected exam for this class.')) ?></div><?php endif; ?>
<?php $scripts = "<script>document.querySelectorAll('.mark-in').forEach(i=>{const f=()=>i.classList.toggle('is-invalid',i.value!==''&&Number(i.value)<Number(i.dataset.pass));i.addEventListener('input',f);f();});</script>"; ?>
