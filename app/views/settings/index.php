<?php
partial('page_head', ['title' => t('School Settings'), 'icon' => 'bi-gear', 'crumbs' => [t('Administration') => null, t('Settings') => null]]);
$tabs = ['general' => ['School Profile', 'bi-building'], 'academic' => ['Academic & Timing', 'bi-calendar3'], 'fees' => ['Fees & Numbering', 'bi-cash-coin'], 'grading' => ['Grading', 'bi-award'],
    'receipt' => ['Receipts', 'bi-receipt'], 'idcard' => ['ID Card & Library', 'bi-person-vcard'], 'notify' => ['Notifications', 'bi-bell'], 'backup' => ['Backup', 'bi-database']];
?>
<div class="row g-4">
    <div class="col-lg-3">
        <div class="card"><div class="list-group list-group-flush" role="tablist">
            <?php foreach ($tabs as $k => [$l, $ic]): ?><a class="list-group-item list-group-item-action <?= $k === 'general' ? 'active' : '' ?>" data-bs-toggle="list" href="#tab-<?= $k ?>" role="tab"><i class="bi <?= $ic ?> me-2"></i><?= e(t($l)) ?></a><?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-lg-9"><div class="tab-content">
    <?php foreach ($tabs as $k => [$l]): ?>
        <div class="tab-pane fade <?= $k === 'general' ? 'show active' : '' ?>" id="tab-<?= $k ?>" role="tabpanel">
            <form method="post" action="<?= e(url('settings/save')) ?>" enctype="multipart/form-data" class="card">
                <?= csrf_field() ?><input type="hidden" name="tab" value="<?= $k ?>">
                <div class="card-header"><?= e(t($l)) ?></div>
                <div class="card-body"><div class="row g-3">
                    <?php if ($k === 'general'): ?>
                        <div class="col-12 d-flex align-items-center gap-3">
                            <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" style="height:70px" class="img-thumbnail" alt=""><?php endif; ?>
                            <div class="flex-grow-1"><label class="form-label"><?= e(t('School Logo')) ?></label><input type="file" name="school_logo" class="form-control" accept="image/png,image/jpeg,image/webp" data-preview>
                                <?php if (setting('school_logo')): ?><label class="form-check small mt-1"><input type="checkbox" class="form-check-input" name="remove_logo" value="1"> <?= e(t('Remove logo')) ?></label><?php endif; ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($k === 'grading'): ?><div class="col-12"><div class="alert alert-light border small mb-0"><?= e(t('Grade ranges are managed in')) ?> <a href="<?= e(url('crud', ['res' => 'grades'])) ?>"><?= e(t('Examinations → Grading')) ?></a>.</div></div><?php endif; ?>
                    <?php if ($k === 'academic'): ?><div class="col-12"><div class="alert alert-light border small mb-0"><?= e(t('Academic sessions are managed in')) ?> <a href="<?= e(url('crud', ['res' => 'sessions'])) ?>"><?= e(t('Academics → Sessions')) ?></a>. <?= e(t('Current')) ?>: <b><?= e(current_session()['name'] ?? '-') ?></b></div></div>
                        <div class="col-12"><label class="form-label"><?= e(t('Working Days')) ?></label><div class="d-flex flex-wrap gap-3">
                            <?php foreach (days_of_week() as $n => $d): ?><label class="form-check"><input type="checkbox" class="form-check-input" name="working_days[]" value="<?= $n ?>" <?= in_array($n, school_days(), true) ? 'checked' : '' ?>> <?= e(t($d)) ?></label><?php endforeach; ?></div></div>
                    <?php endif; ?>
                    <?php foreach ($schema as $key => $s): if ($s[3] !== $k) continue;
                        $type = $s[1]; $val = setting($key, ''); $col = in_array($type, ['textarea'], true) || $key === 'sms_api_url' ? 12 : 6; ?>
                        <?php if ($type === 'color'): ?>
                            <div class="col-md-6"><label class="form-label"><?= e(t($s[0])) ?></label><input type="color" name="<?= e($key) ?>" class="form-control form-control-color" value="<?= e($val ?: '#1e40af') ?>"></div>
                        <?php else: ?>
                            <?= form_field($key, ['label' => $s[0], 'type' => $type === 'number' ? 'decimal' : $type, 'rules' => $s[2], 'options' => $s[4] ?? [], 'col' => $col, 'placeholder' => $type === 'select' ? null : null], $type === 'checkbox' ? ($val === '1') : $val) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($k === 'notify'): ?>
                        <div class="col-12"><div class="alert alert-light border small mb-0"><?= e(t('Example (Pakistani SMS gateways usually provide a URL like this)')) ?>: <code>https://api.provider.pk/send?key=YOURKEY&amp;to={to}&amp;msg={message}</code></div></div>
                        <div class="col-md-8"><div class="input-group"><input type="text" id="testTo" class="form-control" placeholder="<?= e(t('Phone (03xx…) or email for a test')) ?>"><button type="button" class="btn btn-outline-secondary" onclick="(async()=>{const fd=new FormData();fd.append('to',document.getElementById('testTo').value);const r=await api(APP.base+'/index.php?r=settings/test_message',{method:'POST',body:fd});toast(r.message,r.ok?'success':'danger')})()"><?= e(t('Send test')) ?></button></div></div>
                    <?php endif; ?>
                    <?php if ($k === 'backup'): ?><div class="col-12"><a href="<?= e(url('system/backup')) ?>" class="btn btn-outline-primary"><i class="bi bi-database-down"></i> <?= e(t('Open Backup & Restore')) ?></a></div><?php endif; ?>
                </div></div>
                <div class="card-body border-top text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Changes')) ?></button></div>
            </form>
        </div>
    <?php endforeach; ?>
    </div></div>
</div>
<?php $scripts = "<script>if(location.hash){const a=document.querySelector('[href=\"#tab-'+location.hash.slice(1)+'\"]');if(a)bootstrap.Tab.getOrCreateInstance(a).show();}</script>"; ?>
