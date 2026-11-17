<?php partial('page_head', ['title' => t('My Profile'), 'icon' => 'bi-person-circle']); ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><?= e(t('Profile Information')) ?></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" action="<?= e(url('auth/profile')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if ($u['photo']): ?><img src="<?= e(upload_url($u['photo'])) ?>" class="avatar-lg" alt=""><?php else: ?><span class="avatar-lg avatar-ph"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span><?php endif; ?>
                        <div><div class="fw-bold fs-5"><?= e($u['name']) ?></div><div class="text-muted">@<?= e($u['username']) ?> &middot; <?= e(t(Auth::user()['role_name'])) ?></div>
                            <div class="small text-muted"><?= e(t('Last login')) ?>: <?= e(fmt_date($u['last_login'], 'd-M-Y h:i A')) ?></div></div>
                    </div>
                    <div class="row g-3">
                        <?= form_field('name', ['label' => 'Full Name', 'rules' => 'required|max:120'], $u['name']) ?>
                        <?= form_field('email', ['label' => 'Email', 'type' => 'email'], $u['email']) ?>
                        <?= form_field('phone', ['label' => 'Phone'], $u['phone']) ?>
                        <?= form_field('photo', ['label' => 'Photo', 'type' => 'image']) ?>
                    </div>
                    <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?= e(t('Save')) ?></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-4" id="password">
            <div class="card-header"><?= e(t('Change Password')) ?></div>
            <div class="card-body">
                <form method="post" action="<?= e(url('auth/profile')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="action" value="password">
                    <div class="mb-3"><label class="form-label"><?= e(t('Current Password')) ?></label><input type="password" name="current_password" class="form-control" required autocomplete="current-password"></div>
                    <div class="mb-3"><label class="form-label"><?= e(t('New Password')) ?></label><input type="password" name="password" class="form-control" minlength="8" required autocomplete="new-password"><div class="form-text"><?= e(t('At least 8 characters with letters and numbers.')) ?></div></div>
                    <div class="mb-3"><label class="form-label"><?= e(t('Confirm Password')) ?></label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
                    <button class="btn btn-primary"><i class="bi bi-key"></i> <?= e(t('Update Password')) ?></button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><?= e(t('Recent Account Activity')) ?></div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($logins as $l): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><?= e(ucwords(str_replace('_', ' ', $l['action']))) ?> <span class="text-muted">· <?= e($l['ip']) ?></span></span><span class="text-muted"><?= e(fmt_date($l['created_at'], 'd-M h:i A')) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
