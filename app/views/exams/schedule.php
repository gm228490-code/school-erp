<?php partial('page_head', ['title' => t('Exam Schedule'), 'icon' => 'bi-calendar-event', 'crumbs' => [t('Examinations') => url('crud', ['res' => 'exams']), t('Schedule') => null],
    'actions' => $examId ? '<a target="_blank" class="btn btn-sm btn-outline-secondary" href="' . e(url('exams/datesheet', ['exam_id' => $examId])) . '"><i class="bi bi-printer"></i> ' . e(t('Print Date Sheet')) . '</a>' : '']); ?>
<?php if (!$exams): ?><div class="alert alert-warning"><?= e(t('Create an exam first.')) ?> <a href="<?= e(url('crud', ['res' => 'exams'])) ?>"><?= e(t('Exams')) ?></a></div><?php endif; ?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="exams/schedule">
        <div class="col-md-5"><label class="form-label"><?= e(t('Exam')) ?></label><select name="exam_id" class="form-select" required><?= options($exams, $examId, t('-- Select --')) ?></select></div>
        <div class="col-md-4"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" class="form-select" required><?= options(class_options(), $classId, t('-- Select --')) ?></select></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Load')) ?></button></div>
    </form>
</div></div>
<div class="row g-4">
    <div class="col-xl-8">
    <?php if ($rows): ?>
        <form method="post" action="<?= e(url('exams/save_schedule')) ?>" class="card">
            <?= csrf_field() ?><input type="hidden" name="exam_id" value="<?= (int)$examId ?>"><input type="hidden" name="class_id" value="<?= (int)$classId ?>">
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th><input type="checkbox" class="form-check-input" data-check-all=".chk-sub"></th><th><?= e(t('Subject')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Time')) ?></th><th><?= e(t('Total Marks')) ?></th><th><?= e(t('Pass Marks')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): $x = $r['row']; $k = (int)$r['subject_id']; ?>
                    <tr><td><input type="checkbox" class="form-check-input chk-sub" name="sub[<?= $k ?>][include]" value="1" <?= $x ? 'checked' : '' ?>></td><td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><input type="date" class="form-control form-control-sm" name="sub[<?= $k ?>][date]" value="<?= e($x['exam_date'] ?? '') ?>"></td>
                        <td><input type="time" class="form-control form-control-sm" name="sub[<?= $k ?>][time]" value="<?= e(isset($x['start_time']) ? substr((string)$x['start_time'], 0, 5) : '') ?>"></td>
                        <td><input type="number" step="0.5" min="1" class="form-control form-control-sm" name="sub[<?= $k ?>][total]" value="<?= e((float)($x['total_marks'] ?? 100)) ?>"></td>
                        <td><input type="number" step="0.5" min="0" class="form-control form-control-sm" name="sub[<?= $k ?>][pass]" value="<?= e((float)($x['pass_marks'] ?? 33)) ?>"></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <div class="card-body border-top d-flex justify-content-between align-items-center"><span class="small text-muted"><?= e(t('Tick the subjects included in this exam for the class. Subjects with entered marks cannot be removed.')) ?></span><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Schedule')) ?></button></div>
        </form>
    <?php elseif ($examId && $classId): ?><div class="alert alert-warning"><?= e(t('No subjects found. Create subjects and assign them to the class first.')) ?></div><?php endif; ?>
    </div>
    <?php if ($overview): ?>
    <div class="col-xl-4"><div class="card"><div class="card-header"><?= e(t('Scheduled Classes')) ?></div>
        <table class="table table-sm mb-0"><thead><tr><th><?= e(t('Class')) ?></th><th><?= e(t('Papers')) ?></th><th><?= e(t('Dates')) ?></th></tr></thead><tbody>
        <?php foreach ($overview as $o): ?><tr><td><?= e($o['name']) ?></td><td><?= (int)$o['n'] ?></td><td class="small"><?= e(fmt_date($o['first'])) ?> – <?= e(fmt_date($o['last'])) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    <?php endif; ?>
</div>
