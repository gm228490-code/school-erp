<?php partial('page_head', ['title' => t('Search'), 'icon' => 'bi-search']); ?>
<form method="get" class="mb-4"><input type="hidden" name="r" value="search">
    <div class="input-group input-group-lg"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" name="q" class="form-control" value="<?= e($q) ?>" placeholder="<?= e(t('Search students, staff, receipts, books…')) ?>" autofocus><button class="btn btn-primary"><?= e(t('Search')) ?></button></div>
</form>
<?php if ($q !== '' && !$groups): ?><div class="card"><div class="empty-state"><i class="bi bi-search"></i><?= e(t('No results for')) ?> "<?= e($q) ?>"</div></div><?php endif; ?>
<div class="row g-4">
<?php foreach ($groups as $label => [$icon, $items]): ?>
    <div class="col-lg-6"><div class="card"><div class="card-header"><i class="bi <?= e($icon) ?> me-1"></i><?= e(t($label)) ?> <span class="badge text-bg-primary badge-soft"><?= count($items) ?></span></div>
        <div class="list-group list-group-flush">
            <?php foreach ($items as $it): ?><a class="list-group-item list-group-item-action" href="<?= e($it['url']) ?>"><div class="fw-semibold"><?= e($it['title']) ?></div><div class="small text-muted"><?= e($it['sub']) ?></div></a><?php endforeach; ?>
        </div></div></div>
<?php endforeach; ?>
</div>
