<?php
$user = Auth::user();
$menu = Auth::isPortal() ? [[t('My Portal'), [
    ['Home', 'bi-house', 'portal', [], ''],
    ['Profile', 'bi-person', 'portal/profile', [], ''],
    ['Attendance', 'bi-calendar-check', 'portal/attendance', [], ''],
    ['Results', 'bi-trophy', 'portal/results', [], ''],
    ['Fees', 'bi-cash-coin', 'portal/fees', [], ''],
    ['Homework', 'bi-journal-text', 'portal/homework', [], ''],
    ['Timetable', 'bi-calendar3-week', 'portal/timetable', [], ''],
    ['Announcements', 'bi-megaphone', 'portal/notices', [], ''],
    ['Notifications', 'bi-bell', 'notifications', [], ''],
]]] : require APP_PATH . '/menu.php';
$curRoute = (string)($_GET['r'] ?? 'dashboard');
$curFull = $curRoute . (isset($_GET['res']) ? '?res=' . $_GET['res'] : '') ;
$theme = $_COOKIE['theme'] ?? ($user['theme'] ?? 'light');
$theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
$unread = $user ? (int)DB::val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$user['id']]) : 0;
$sess = current_session();
$logo = setting('school_logo');
$isActive = function (array $item) use ($curRoute): bool {
    [$label, $icon, $route, $params] = $item;
    $match = $item[5] ?? null;
    if (isset($params['res'])) {
        return str_starts_with($curRoute, 'crud') && ($_GET['res'] ?? '') === $params['res'];
    }
    if (isset($params['type'])) {
        return str_starts_with($curRoute, 'employees') && ($_GET['type'] ?? '') === $params['type'];
    }
    $r = str_contains($curRoute, '/') ? $curRoute : $curRoute . '/index';
    $target = str_contains($route, '/') ? $route : $route . '/index';
    if ($match) {
        return in_array($r, explode('|', $match), true);
    }
    return $r === $target;
};
?><!doctype html>
<html lang="<?= e(lang()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-bs-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($title ?? t('Dashboard')) . ' | ' . setting('school_name', 'School ERP')) ?></title>
    <link rel="icon" href="<?= $logo ? e(upload_url($logo)) : e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/css/bootstrap.rtl.min.css' : 'vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="<?= is_rtl() ? 'rtl' : '' ?>">
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt="" class="brand-logo"><?php else: ?><span class="brand-icon"><i class="bi bi-mortarboard-fill"></i></span><?php endif; ?>
            <div class="brand-text"><strong><?= e(setting('school_short_name', setting('school_name', 'School ERP'))) ?></strong><small><?= e(t('Management System')) ?></small></div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($menu as [$group, $items]):
                $visible = array_filter($items, fn($i) => $i[4] === '' || Auth::canAny(...(array)$i[4]));
                if (!$visible) continue; ?>
                <div class="nav-group"><?= e(t($group)) ?></div>
                <?php foreach ($visible as $item): ?>
                    <a href="<?= e(url($item[2], $item[3])) ?>" class="nav-item <?= $isActive($item) ? 'active' : '' ?>">
                        <i class="bi <?= e($item[1]) ?>"></i><span><?= e(t($item[0])) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot small">v<?= APP_VERSION ?> &middot; <?= e($sess['name'] ?? '') ?></div>
    </aside>
    <div class="sidebar-backdrop" data-toggle-sidebar></div>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-icon" data-toggle-sidebar aria-label="Menu"><i class="bi bi-list fs-4"></i></button>
            <?php if (!Auth::isPortal()): ?><form class="topbar-search d-none d-md-flex" action="<?= e(base_url() . '/index.php') ?>" method="get">
                <input type="hidden" name="r" value="search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="<?= e(t('Search students, staff, receipts, books…')) ?>" value="<?= ($_GET['r'] ?? '') === 'search' ? e($_GET['q'] ?? '') : '' ?>">
            </form><?php endif; ?>
            <div class="ms-auto d-flex align-items-center gap-1">
                <?php if ($sess && !Auth::isPortal()): ?>
                <div class="dropdown d-none d-sm-block">
                    <button class="btn btn-sm btn-light-soft dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-calendar2-week"></i> <?= e($sess['name']) ?></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach (session_options() as $sid => $sname): ?>
                            <li><form method="post" action="<?= e(url('lang/session')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$sid ?>"><button class="dropdown-item <?= $sid == $sess['id'] ? 'active' : '' ?>"><?= e($sname) ?></button></form></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('lang/switch')) ?>" class="d-inline"><?= csrf_field() ?>
                    <input type="hidden" name="lang" value="<?= is_rtl() ? 'en' : 'ur' ?>">
                    <button class="btn btn-icon" title="<?= is_rtl() ? 'English' : 'اردو' ?>"><span class="fw-semibold small"><?= is_rtl() ? 'EN' : 'اردو' ?></span></button>
                </form>
                <button class="btn btn-icon" id="themeToggle" title="<?= e(t('Toggle theme')) ?>"><i class="bi <?= $theme === 'dark' ? 'bi-sun' : 'bi-moon-stars' ?>"></i></button>
                <div class="dropdown">
                    <button class="btn btn-icon position-relative" data-bs-toggle="dropdown" id="notifBtn" aria-label="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notif-dot <?= $unread ? '' : 'd-none' ?>" id="notifCount"><?= $unread > 99 ? '99+' : $unread ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-menu p-0">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong><?= e(t('Notifications')) ?></strong>
                            <a href="<?= e(url('notifications')) ?>" class="small"><?= e(t('View all')) ?></a>
                        </div>
                        <div id="notifList" class="notif-list"><div class="p-3 text-muted small"><?= e(t('Loading…')) ?></div></div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn d-flex align-items-center gap-2 user-btn" data-bs-toggle="dropdown">
                        <?php if (!empty($user['photo'])): ?><img src="<?= e(upload_url($user['photo'])) ?>" class="avatar-sm" alt=""><?php else: ?><span class="avatar-sm avatar-ph"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><?php endif; ?>
                        <span class="d-none d-lg-block text-start lh-sm"><span class="d-block fw-semibold small"><?= e($user['name']) ?></span><span class="d-block text-muted" style="font-size:.72rem"><?= e(t($user['role_name'])) ?></span></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= e(url('auth/profile')) ?>"><i class="bi bi-person me-2"></i><?= e(t('My Profile')) ?></a></li>
                        <li><a class="dropdown-item" href="<?= e(url('auth/profile')) ?>#password"><i class="bi bi-key me-2"></i><?= e(t('Change Password')) ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="post" action="<?= e(url('auth/logout')) ?>"><?= csrf_field() ?><button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i><?= e(t('Logout')) ?></button></form></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            <?php foreach (flashes() as [$type, $msg]): ?>
                <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi <?= $type === 'success' ? 'bi-check-circle' : ($type === 'danger' ? 'bi-exclamation-octagon' : 'bi-info-circle') ?> me-2"></i><?= e($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
        <footer class="footer small text-muted">
            &copy; <?= date('Y') ?> <?= e(setting('school_name', 'School ERP')) ?> &middot; <?= e(t('School Management System')) ?>
        </footer>
    </div>
</div>

<!-- Generic AJAX modal -->
<div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"></div></div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastArea"></div>

<script>window.APP = {base: <?= json_encode(base_url()) ?>, csrf: <?= json_encode(csrf_token()) ?>, lang: <?= json_encode(lang()) ?>, i18n: <?= json_encode(['confirm' => t('Are you sure?'), 'deleted' => t('Deleted successfully'), 'saved' => t('Saved successfully'), 'error' => t('Something went wrong'), 'none' => t('No new notifications'), 'notFound' => t('No student found'), 'yes' => t('Yes, continue'), 'cancel' => t('Cancel')], JSON_UNESCAPED_UNICODE) ?>};</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($scripts)) echo $scripts; ?>
</body>
</html>
