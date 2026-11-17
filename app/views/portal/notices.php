<?php $icon = 'bi-megaphone'; include __DIR__ . '/_head.php'; ?>
<?php foreach ($notices as $n): ?>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between"><h6 class="fw-bold"><?= e($n['title']) ?></h6><span class="badge text-bg-primary badge-soft"><?= e(t(ucfirst($n['notice_type']))) ?></span></div>
    <div class="small text-muted mb-2"><?= e(fmt_date($n['publish_date'], 'l, d F Y')) ?></div>
    <div><?= nl2br(e($n['body'])) ?></div>
</div></div>
<?php endforeach; ?>
<?php if (!$notices): ?><div class="card"><div class="empty-state"><i class="bi bi-megaphone"></i><?= e(t('No announcements.')) ?></div></div><?php endif; ?>
