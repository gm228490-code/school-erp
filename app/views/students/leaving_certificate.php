<div style="border:3px double #1e3a8a;padding:24px 28px;margin-top:10px;font-size:14px;line-height:2.1">
    <div class="d-flex justify-content-between small"><span><?= e(t('Certificate No')) ?>: SLC-<?= e($s['reg_no']) ?></span><span><?= e(t('Date')) ?>: <?= e(date('d-M-Y')) ?></span></div>
    <p class="mt-3"><?= e(t('This is to certify that')) ?> <b><?= e(student_name($s)) ?></b>, <?= $s['gender'] === 'female' ? 'd/o' : 's/o' ?> <b><?= e($par['father_name'] ?? '________') ?></b>,
        <?= e(t('Registration No.')) ?> <b><?= e($s['reg_no']) ?></b>, <?= e(t('was a bonafide student of this school from')) ?> <b><?= e(fmt_date($s['admission_date'])) ?></b>
        <?= e(t('to')) ?> <b><?= e(fmt_date($s['status_date'] ?: date('Y-m-d'))) ?></b>.</p>
    <p><?= e(t('He/She was studying in')) ?> <b><?= e($s['class_name'] . ' ' . $s['section_name']) ?></b>. <?= e(t('Date of birth according to school record is')) ?> <b><?= e(fmt_date($s['dob'])) ?></b>
        (<?= $s['dob'] ? e(date('jS F Y', strtotime($s['dob']))) : '' ?>).</p>
    <p><?= e(t('Reason for leaving')) ?>: <b><?= e($s['status_reason'] ?: '________________') ?></b></p>
    <p><?= e(t('Character and conduct')) ?>: <b><?= e(t('Good')) ?></b>. <?= e(t('All dues have been cleared.')) ?></p>
    <p><?= e(t('We wish him/her success in future.')) ?></p>
    <div class="sig-row"><div><?= e(t('Class Teacher')) ?></div><div><?= e(t('Accounts Office')) ?></div><div><?= e(setting('principal_name') ?: t('Principal')) ?><br><?= e(t('Principal')) ?></div></div>
</div>
