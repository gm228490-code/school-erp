<!doctype html>
<html lang="<?= e(lang()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-bs-theme="<?= e(in_array($_COOKIE['theme'] ?? 'light', ['light', 'dark'], true) ? ($_COOKIE['theme'] ?? 'light') : 'light') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Welcome') . ' | ' . setting('school_name', 'School ERP')) ?></title>
    <link rel="icon" href="<?= setting('school_logo') ? e(upload_url(setting('school_logo'))) : e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/css/bootstrap.rtl.min.css' : 'vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="<?= is_rtl() ? 'rtl' : '' ?>">
<?= $content ?>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
