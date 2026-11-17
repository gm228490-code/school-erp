<?php $icon = 'bi-person'; include __DIR__ . '/_head.php'; ?>
<div class="card"><div class="card-body">
    <div class="d-flex gap-4 flex-wrap">
        <?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'])) ?>" class="avatar-lg" alt=""><?php else: ?><span class="avatar-lg avatar-ph"><?= e(mb_substr($s['first_name'], 0, 1)) ?></span><?php endif; ?>
        <dl class="kv flex-grow-1">
            <dt><?= e(t('Name')) ?></dt><dd class="fw-semibold"><?= e(student_name($s)) ?></dd>
            <dt><?= e(t('Registration No')) ?></dt><dd><?= e($s['reg_no']) ?></dd>
            <dt><?= e(t('Class')) ?></dt><dd><?= e($s['class_name'] . ' ' . $s['section_name']) ?> · <?= e(t('Roll')) ?> <?= e($s['roll_no']) ?></dd>
            <dt><?= e(t('Date of Birth')) ?></dt><dd><?= e(fmt_date($s['dob'])) ?></dd>
            <dt><?= e(t('Gender')) ?></dt><dd><?= e(t(ucfirst($s['gender']))) ?></dd>
            <dt><?= e(t('Blood Group')) ?></dt><dd><?= e($s['blood_group']) ?></dd>
            <dt><?= e(t("Father's Name")) ?></dt><dd><?= e($s['father_name']) ?></dd>
            <dt><?= e(t("Mother's Name")) ?></dt><dd><?= e($s['mother_name']) ?></dd>
            <dt><?= e(t('Contact')) ?></dt><dd><?= e($s['father_phone'] ?: $s['phone']) ?></dd>
            <dt><?= e(t('Address')) ?></dt><dd><?= e(trim($s['address'] . ' ' . $s['city'])) ?></dd>
            <dt><?= e(t('Admission Date')) ?></dt><dd><?= e(fmt_date($s['admission_date'])) ?></dd>
            <dt><?= e(t('Transport')) ?></dt><dd><?= e($s['route_name'] ?: t('None')) ?></dd>
        </dl>
    </div>
    <p class="small text-muted mt-3 mb-0"><?= e(t('To correct any information, please contact the school office.')) ?></p>
</div></div>
