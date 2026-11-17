<!doctype html>
<html lang="<?= e(lang()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Print') . ' - ' . setting('school_name', 'School ERP')) ?></title>
    <link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/css/bootstrap.rtl.min.css' : 'vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap">
    <link rel="stylesheet" href="<?= asset('css/print.css') ?>">
    <?php if (!empty($landscape)): ?><style>@page{size:A4 landscape}</style><?php endif; ?>
</head>
<body class="<?= is_rtl() ? 'rtl' : '' ?>">
<div class="print-toolbar no-print">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> <?= e(t('Print')) ?> / <?= e(t('Save as PDF')) ?></button>
    <button onclick="history.length>1?history.back():window.close()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> <?= e(t('Back')) ?></button>
    <?php if (!empty($exportLinks)) echo $exportLinks; ?>
</div>
<div class="print-page <?= !empty($landscape) ? 'landscape' : '' ?>">
    <?php if (empty($noHeader)): ?>
    <div class="letterhead">
        <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" alt="" class="lh-logo"><?php endif; ?>
        <div class="lh-text">
            <h2><?= e(setting('school_name', 'School ERP')) ?></h2>
            <div><?= e(setting('school_address')) ?></div>
            <div><?= e(implode('  |  ', array_filter([setting('school_phone') ? 'Ph: ' . setting('school_phone') : '', setting('school_email'), setting('school_website')]))) ?></div>
        </div>
    </div>
    <?php if (!empty($title)): ?><h4 class="doc-title"><?= e($title) ?></h4><?php endif; ?>
    <?php if (!empty($subtitle)): ?><div class="doc-subtitle"><?= e($subtitle) ?></div><?php endif; ?>
    <?php endif; ?>
    <?= $content ?>
    <?php if (empty($noHeader)): ?><div class="print-foot">Printed on <?= e(date('d-M-Y h:i A')) ?> by <?= e(Auth::user()['name'] ?? '') ?></div><?php endif; ?>
</div>
<?php if (!empty($autoPrint)): ?><script>window.addEventListener('load',()=>setTimeout(()=>window.print(),300));</script><?php endif; ?>
</body>
</html>
