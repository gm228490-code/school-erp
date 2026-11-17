<?php /** @var string $title @var string $icon @var string $actions @var array $crumbs */ ?>
<div class="page-head">
    <div>
        <h1><span class="ph-icon"><i class="bi <?= e($icon ?? 'bi-grid') ?>"></i></span><?= e($title) ?></h1>
        <?php if (!empty($crumbs)): ?>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('dashboard')) ?>"><?= e(t('Home')) ?></a></li>
            <?php foreach ($crumbs as $label => $link): ?>
                <?php if ($link): ?><li class="breadcrumb-item"><a href="<?= e($link) ?>"><?= e($label) ?></a></li>
                <?php else: ?><li class="breadcrumb-item active"><?= e($label) ?></li><?php endif; ?>
            <?php endforeach; ?>
        </ol></nav>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2"><?= $actions ?? '' ?></div>
</div>
