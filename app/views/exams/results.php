<?php partial('page_head', ['title' => t('Results'), 'icon' => 'bi-trophy', 'crumbs' => [t('Examinations') => url('crud', ['res' => 'exams']), t('Results') => null]]); ?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="exams/results">
        <div class="col-md-4"><label class="form-label"><?= e(t('Exam')) ?></label><select name="exam_id" class="form-select" required><?= options($exams, $examId, t('-- Select --')) ?></select></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="rsClass" class="form-select" required><?= options(class_options(), $classId, t('-- Select --')) ?></select></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#rsClass"><?= options($classId ? section_options($classId) : [], $sectionId, t('All Sections')) ?></select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Show')) ?></button></div>
    </form>
</div></div>
<?php if ($exam && $classId): ?>
<div class="row g-4">
    <div class="col-xl-3">
        <div class="card mb-4"><div class="card-header"><?= e(t('Marks Entry Progress')) ?></div>
            <ul class="list-group list-group-flush small">
            <?php foreach ($progress as $pr): $pct = $pr['strength'] ? min(100, round($pr['entered'] / $pr['strength'] * 100)) : 0; ?>
                <li class="list-group-item"><div class="d-flex justify-content-between"><span><?= e($pr['name']) ?></span><span><?= (int)$pr['entered'] ?>/<?= (int)$pr['strength'] ?></span></div><div class="progress progress-thin mt-1"><div class="progress-bar <?= $pct >= 100 ? 'bg-success' : '' ?>" style="width:<?= $pct ?>%"></div></div></li>
            <?php endforeach; ?>
            <?php if (!$progress): ?><li class="list-group-item text-muted"><?= e(t('No subjects scheduled.')) ?></li><?php endif; ?>
            </ul></div>
        <?php if (can('exams.results')): ?>
        <div class="card"><div class="card-body d-grid gap-2">
            <form method="post" action="<?= e(url('exams/generate')) ?>" data-confirm="<?= e(t('Calculate results now? Existing results for this class will be recalculated.')) ?>"><?= csrf_field() ?>
                <input type="hidden" name="exam_id" value="<?= (int)$examId ?>"><input type="hidden" name="class_id" value="<?= (int)$classId ?>">
                <button class="btn btn-primary w-100"><i class="bi bi-calculator"></i> <?= e(t('Generate / Recalculate Results')) ?></button></form>
            <button class="btn btn-<?= $exam['is_published'] ? 'outline-warning' : 'success' ?>" data-post="<?= e(url('exams/publish')) ?>" data-field-exam_id="<?= (int)$examId ?>" data-field-publish="<?= $exam['is_published'] ? '0' : '1' ?>"
                data-confirm-text="<?= e($exam['is_published'] ? t('Hide results from the portal?') : t('Publish results to parents & students?')) ?>"><i class="bi bi-megaphone"></i> <?= e($exam['is_published'] ? t('Unpublish Results') : t('Publish Results')) ?></button>
        </div></div>
        <?php endif; ?>
    </div>
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2 align-items-center">
                <span><?= e($exam['name']) ?> <?= $exam['is_published'] ? status_badge('approved') : '' ?></span>
                <div class="d-flex gap-2 flex-wrap">
                    <a target="_blank" class="btn btn-sm btn-outline-secondary" href="<?= e(url('exams/result_sheet', ['exam_id' => $examId, 'class_id' => $classId, 'section_id' => $sectionId])) ?>"><i class="bi bi-table"></i> <?= e(t('Result Sheet')) ?></a>
                    <a class="btn btn-sm btn-outline-success" href="<?= e(url('exams/result_sheet', ['exam_id' => $examId, 'class_id' => $classId, 'section_id' => $sectionId, 'export' => 'xls'])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    <a target="_blank" class="btn btn-sm btn-outline-primary" href="<?= e(url('exams/report_card', ['exam_id' => $examId, 'class_id' => $classId, 'section_id' => $sectionId])) ?>"><i class="bi bi-printer"></i> <?= e(t('All Report Cards')) ?></a>
                </div>
            </div>
            <?php if ($rows): $passN = count(array_filter($rows, fn($r) => $r['result_status'] === 'pass')); ?>
                <div class="px-3 pt-3 small"><?= e(t('Students')) ?>: <b><?= count($rows) ?></b> · <?= e(t('Passed')) ?>: <b class="text-success"><?= $passN ?></b> · <?= e(t('Failed')) ?>: <b class="text-danger"><?= count($rows) - $passN ?></b> · <?= e(t('Pass %')) ?>: <b><?= round($passN / count($rows) * 100, 1) ?>%</b> · <?= e(t('Class average')) ?>: <b><?= round(array_sum(array_column($rows, 'percentage')) / count($rows), 1) ?>%</b></div>
            <?php endif; ?>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th><?= e(t('Pos')) ?></th><th><?= e(t('Student')) ?></th><th><?= e(t('Section')) ?></th><th><?= e(t('Marks')) ?></th><th>%</th><th><?= e(t('Grade')) ?></th><th><?= e(t('Result')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr><td class="fw-bold"><?= $r['position'] && $r['position'] <= 3 ? '<i class="bi bi-trophy-fill text-warning"></i> ' : '' ?><?= e(ResultService::ordinal($r['position'] ? (int)$r['position'] : null)) ?></td>
                        <td><?= e(student_name($r)) ?><div class="small text-muted"><?= e($r['reg_no']) ?> · <?= e(t('Roll')) ?> <?= e($r['roll_no']) ?></div></td><td><?= e($r['section_name']) ?></td>
                        <td><?= (float)$r['obtained_marks'] ?> / <?= (float)$r['total_marks'] ?></td><td class="fw-semibold"><?= e($r['percentage']) ?></td><td class="fw-bold"><?= e($r['grade']) ?></td>
                        <td><?= status_badge($r['result_status']) ?><?= $r['subjects_failed'] ? ' <span class="small text-danger">(' . (int)$r['subjects_failed'] . ')</span>' : '' ?></td>
                        <td><a target="_blank" class="btn btn-sm btn-light-soft" href="<?= e(url('exams/report_card', ['exam_id' => $examId, 'student_id' => $r['student_id']])) ?>"><i class="bi bi-file-earmark-text"></i></a></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-trophy"></i><?= e(t('No results yet. Enter marks and click Generate Results.')) ?></div></td></tr><?php endif; ?>
                </tbody></table></div>
        </div>
    </div>
</div>
<?php endif; ?>
