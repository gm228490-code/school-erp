<?php $color = preg_match('/^#[0-9a-fA-F]{6}$/', setting('idcard_color', '#1e40af')) ? setting('idcard_color', '#1e40af') : '#1e40af'; ?>
<div class="id-grid" style="--idc: <?= e($color) ?>">
    <?php foreach ($students as $s): ?>
        <div class="id-card">
            <div class="id-top">
                <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" alt=""><?php endif; ?>
                <div><div class="nm"><?= e(setting('school_name')) ?></div><div class="sub"><?= e(t('STUDENT IDENTITY CARD')) ?> · <?= e(current_session()['name'] ?? '') ?></div></div>
            </div>
            <div class="id-body">
                <?php if ($s['photo']): ?><img class="id-photo" src="<?= e(upload_url($s['photo'])) ?>" alt=""><?php else: ?><div class="id-photo"><?= e(mb_substr($s['first_name'], 0, 1)) ?></div><?php endif; ?>
                <div class="id-info">
                    <div class="n"><?= e(student_name($s)) ?></div>
                    <div><b><?= e(t('Reg No')) ?></b> <?= e($s['reg_no']) ?></div>
                    <div><b><?= e(t('Class')) ?></b> <?= e($s['class_name'] . ($s['section_name'] ? ' - ' . $s['section_name'] : '')) ?></div>
                    <div><b><?= e(t('Roll No')) ?></b> <?= e($s['roll_no']) ?></div>
                    <div><b><?= e(t('Father')) ?></b> <?= e($s['father_name'] ?? DB::val('SELECT father_name FROM parents WHERE id=?', [$s['parent_id']])) ?></div>
                    <div><b><?= e(t('DOB')) ?></b> <?= e(fmt_date($s['dob'])) ?> <?= $s['blood_group'] ? ' · <b>' . e(t('Blood')) . '</b> ' . e($s['blood_group']) : '' ?></div>
                    <div><b><?= e(t('Contact')) ?></b> <?= e($s['father_phone'] ?? $s['phone'] ?? '') ?></div>
                </div>
            </div>
            <div class="id-foot"><span><?= e(setting('idcard_note')) ?></span><span><?= e(setting('school_phone')) ?><?= setting('idcard_validity') ? ' · ' . e(t('Valid till')) . ' ' . e(setting('idcard_validity')) : '' ?></span></div>
        </div>
    <?php endforeach; ?>
    <?php if (!$students): ?><p><?= e(t('No students found.')) ?></p><?php endif; ?>
</div>
