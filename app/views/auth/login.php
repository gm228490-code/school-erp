<div class="auth-wrap">
    <div class="auth-hero">
        <div class="d-flex align-items-center gap-2 position-relative" style="z-index:1">
            <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" class="brand-logo" alt=""><?php else: ?><span class="brand-icon"><i class="bi bi-mortarboard-fill"></i></span><?php endif; ?>
            <strong class="fs-5"><?= e(setting('school_name', 'School ERP')) ?></strong>
        </div>
        <div class="position-relative" style="z-index:1">
            <h1 class="mb-3"><?= e(t('Smart School Management')) ?></h1>
            <p class="opacity-75 mb-4" style="max-width:460px"><?= e(t('Admissions, attendance, fees, examinations, timetable, finance and parent communication in one secure place.')) ?></p>
            <div class="auth-feature"><i class="bi bi-people"></i><?= e(t('Student & staff records')) ?></div>
            <div class="auth-feature"><i class="bi bi-cash-coin"></i><?= e(t('Fee collection with instant receipts')) ?></div>
            <div class="auth-feature"><i class="bi bi-trophy"></i><?= e(t('Results, report cards & positions')) ?></div>
            <div class="auth-feature"><i class="bi bi-phone"></i><?= e(t('Parent & student portal')) ?></div>
        </div>
        <div class="small opacity-50 position-relative" style="z-index:1">&copy; <?= date('Y') ?> <?= e(setting('school_name', 'School ERP')) ?></div>
    </div>
    <div class="auth-form">
        <div class="auth-card">
            <div class="mb-4">
                <h3 class="fw-bold mb-1"><?= e(t('Welcome back')) ?></h3>
                <div class="text-muted"><?= e(t('Sign in to continue to your account')) ?></div>
            </div>
            <?php foreach (flashes() as [$type, $msg]): ?><div class="alert alert-<?= e($type) ?> py-2"><?= e($msg) ?></div><?php endforeach; ?>
            <?php if ($error): ?><div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?></div><?php endif; ?>
            <form method="post" action="<?= e(url('auth/login')) ?>" autocomplete="on">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label"><?= e(t('Username or Email')) ?></label>
                    <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="login" class="form-control form-control-lg" value="<?= e($_POST['login'] ?? '') ?>" required autofocus autocomplete="username"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= e(t('Password')) ?></label>
                    <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pw" class="form-control form-control-lg" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary" onclick="const p=document.getElementById('pw');p.type=p.type==='password'?'text':'password'"><i class="bi bi-eye"></i></button></div>
                </div>
                <button class="btn btn-primary btn-lg w-100 mt-2"><i class="bi bi-box-arrow-in-right me-1"></i> <?= e(t('Sign In')) ?></button>
            </form>
            <div class="text-center mt-4 small text-muted">
                <form method="post" action="<?= e(url('lang/switch')) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="lang" value="<?= is_rtl() ? 'en' : 'ur' ?>"><button class="btn btn-link btn-sm"><?= is_rtl() ? 'English' : 'اردو' ?></button></form>
                &middot; <?= e(t('Parents & students use the credentials provided by the school.')) ?>
            </div>
        </div>
    </div>
</div>
