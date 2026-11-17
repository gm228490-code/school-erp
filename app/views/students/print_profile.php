<?php $tot = array_sum(array_map('intval', $att)); $pres = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0); ?>
<div class="d-flex gap-3 mb-3">
    <div class="flex-grow-1">
        <div class="kv-print">
            <b><?= e(t('Name')) ?>:</b><span><?= e(student_name($s)) ?></span><b><?= e(t('Reg No')) ?>:</b><span><?= e($s['reg_no']) ?></span>
            <b><?= e(t('Class')) ?>:</b><span><?= e($s['class_name'] . ' ' . $s['section_name']) ?></span><b><?= e(t('Roll No')) ?>:</b><span><?= e($s['roll_no']) ?></span>
            <b><?= e(t('Gender')) ?>:</b><span><?= e(ucfirst($s['gender'])) ?></span><b><?= e(t('Date of Birth')) ?>:</b><span><?= e(fmt_date($s['dob'])) ?></span>
            <b><?= e(t('B-Form')) ?>:</b><span><?= e($s['b_form']) ?></span><b><?= e(t('Blood Group')) ?>:</b><span><?= e($s['blood_group']) ?></span>
            <b><?= e(t('Religion')) ?>:</b><span><?= e($s['religion']) ?></span><b><?= e(t('Nationality')) ?>:</b><span><?= e($s['nationality']) ?></span>
            <b><?= e(t('Admission Date')) ?>:</b><span><?= e(fmt_date($s['admission_date'])) ?></span><b><?= e(t('Status')) ?>:</b><span><?= e(ucfirst($s['status'])) ?></span>
            <b><?= e(t('Phone')) ?>:</b><span><?= e($s['phone']) ?></span><b><?= e(t('Transport')) ?>:</b><span><?= e($s['route_name'] ?: '-') ?></span>
            <b><?= e(t('Previous School')) ?>:</b><span style="grid-column: span 3"><?= e($s['previous_school']) ?> <?= e($s['previous_class']) ?></span>
            <b><?= e(t('Address')) ?>:</b><span style="grid-column: span 3"><?= e($s['address'] . ' ' . $s['city']) ?></span>
        </div>
    </div>
    <?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'])) ?>" class="photo-box" alt=""><?php else: ?><div class="photo-box d-flex align-items-center justify-content-center text-muted">Photo</div><?php endif; ?>
</div>
<?php if ($par): ?>
<h6 class="fw-bold border-bottom pb-1"><?= e(t('Parent / Guardian')) ?></h6>
<div class="kv-print">
    <b><?= e(t("Father's Name")) ?>:</b><span><?= e($par['father_name']) ?></span><b><?= e(t('CNIC')) ?>:</b><span><?= e($par['father_cnic']) ?></span>
    <b><?= e(t('Phone')) ?>:</b><span><?= e($par['father_phone']) ?></span><b><?= e(t('Occupation')) ?>:</b><span><?= e($par['father_occupation']) ?></span>
    <b><?= e(t("Mother's Name")) ?>:</b><span><?= e($par['mother_name']) ?></span><b><?= e(t("Mother's Phone")) ?>:</b><span><?= e($par['mother_phone']) ?></span>
    <b><?= e(t('Guardian')) ?>:</b><span><?= e(trim($par['guardian_name'] . ' ' . ($par['guardian_relation'] ? '(' . $par['guardian_relation'] . ')' : ''))) ?></span><b><?= e(t('Email')) ?>:</b><span><?= e($par['email']) ?></span>
</div>
<?php endif; ?>
<h6 class="fw-bold border-bottom pb-1"><?= e(t('Attendance (current session)')) ?></h6>
<p><?= e(t('Present')) ?>: <?= $pres ?> · <?= e(t('Absent')) ?>: <?= (int)($att['absent'] ?? 0) ?> · <?= e(t('Leave')) ?>: <?= (int)($att['leave'] ?? 0) ?> · <?= e(t('Attendance')) ?>: <?= $tot ? round($pres / $tot * 100, 1) : 0 ?>%</p>
<h6 class="fw-bold border-bottom pb-1"><?= e(t('History')) ?></h6>
<table class="table table-bordered table-sm"><thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Action')) ?></th><th><?= e(t('Details')) ?></th></tr></thead><tbody>
<?php foreach ($history as $h): ?><tr><td><?= e(fmt_date($h['created_at'])) ?></td><td><?= e(ucwords(str_replace('_', ' ', $h['action']))) ?></td><td><?= e($h['details']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<div class="sig-row"><div><?= e(t('Class Teacher')) ?></div><div><?= e(t('Principal')) ?></div></div>
