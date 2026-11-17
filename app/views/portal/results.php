<?php $icon = 'bi-trophy'; include __DIR__ . '/_head.php'; ?>
<?php if ($upcoming): ?>
<div class="card mb-4"><div class="card-header"><i class="bi bi-calendar-event me-1"></i><?= e(t('Upcoming Exam Papers')) ?></div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= e(t('Exam')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Time')) ?></th><th><?= e(t('Subject')) ?></th></tr></thead><tbody>
    <?php foreach ($upcoming as $u): ?><tr><td><?= e($u['name']) ?></td><td><?= e(fmt_date($u['exam_date'], 'D, d M Y')) ?></td><td><?= e(fmt_time($u['start_time'])) ?></td><td><?= e($u['subject']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
<?php endif; ?>
<?php foreach ($results as $r): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><b><?= e($r['exam_name']) ?></b> <span class="text-muted small">· <?= e($r['session_name']) ?></span></span>
        <div class="d-flex gap-2 align-items-center"><?= status_badge($r['result_status']) ?> <span class="badge text-bg-primary badge-soft"><?= e($r['percentage']) ?>% · <?= e($r['grade']) ?> · <?= e(t('Position')) ?> <?= e(ResultService::ordinal($r['position'] ? (int)$r['position'] : null)) ?></span>
            <a target="_blank" class="btn btn-sm btn-outline-primary" href="<?= e(url('exams/report_card', ['exam_id' => $r['exam_id'], 'student_id' => $s['id']])) ?>"><i class="bi bi-download"></i> <?= e(t('Report Card')) ?></a></div>
    </div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= e(t('Subject')) ?></th><th><?= e(t('Total')) ?></th><th><?= e(t('Obtained')) ?></th><th><?= e(t('Status')) ?></th></tr></thead><tbody>
    <?php foreach ($details[$r['exam_id']] as $d): $fail = $d['is_absent'] || $d['obtained_marks'] === null || (float)$d['obtained_marks'] < (float)$d['pass_marks']; ?>
        <tr><td><?= e($d['name']) ?></td><td><?= (float)$d['total_marks'] ?></td><td class="<?= $fail ? 'text-danger fw-semibold' : '' ?>"><?= $d['is_absent'] ? e(t('Absent')) : ($d['obtained_marks'] === null ? '-' : (float)$d['obtained_marks']) ?></td><td><?= status_badge($fail ? 'fail' : 'pass') ?></td></tr>
    <?php endforeach; ?>
    <tr class="fw-bold"><td><?= e(t('Total')) ?></td><td><?= (float)$r['total_marks'] ?></td><td><?= (float)$r['obtained_marks'] ?></td><td></td></tr>
    </tbody></table></div>
</div>
<?php endforeach; ?>
<?php if (!$results): ?><div class="card"><div class="empty-state"><i class="bi bi-trophy"></i><?= e(t('No published results yet.')) ?></div></div><?php endif; ?>
