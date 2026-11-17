<?php $color = preg_match('/^#[0-9a-fA-F]{6}$/', setting('idcard_color', '#1e40af')) ? setting('idcard_color', '#1e40af') : '#1e40af'; ?>
<div class="id-grid" style="--idc: <?= e($color) ?>">
<?php foreach ($rows as $r): ?>
    <div class="id-card">
        <div class="id-top">
            <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" alt=""><?php endif; ?>
            <div><div class="nm"><?= e(setting('school_name')) ?></div><div class="sub"><?= e($type === 'teacher' ? t('TEACHER IDENTITY CARD') : t('STAFF IDENTITY CARD')) ?></div></div>
        </div>
        <div class="id-body">
            <?php if ($r['photo']): ?><img class="id-photo" src="<?= e(upload_url($r['photo'])) ?>" alt=""><?php else: ?><div class="id-photo"><?= e(mb_substr($r['name'], 0, 1)) ?></div><?php endif; ?>
            <div class="id-info">
                <div class="n"><?= e($r['name']) ?></div>
                <div><b><?= e(t('ID')) ?></b> <?= e($r['employee_no']) ?></div>
                <div><b><?= e(t('Designation')) ?></b> <?= e($r['desig'] ?: ($type === 'teacher' ? 'Teacher' : 'Staff')) ?></div>
                <div><b><?= e(t('CNIC')) ?></b> <?= e($r['cnic']) ?></div>
                <div><b><?= e(t('Phone')) ?></b> <?= e($r['phone']) ?></div>
                <div><b><?= e(t('Joined')) ?></b> <?= e(fmt_date($r['joining_date'])) ?></div>
            </div>
        </div>
        <div class="id-foot"><span><?= e(setting('idcard_note')) ?></span><span><?= e(setting('school_phone')) ?></span></div>
    </div>
<?php endforeach; ?>
</div>
