<?php partial('page_head', ['title' => t('Notifications'), 'icon' => 'bi-bell', 'actions' =>
    '<form method="post" action="' . e(url('notifications/read_all')) . '">' . csrf_field() . '<button class="btn btn-sm btn-outline-primary"><i class="bi bi-check2-all"></i> ' . e(t('Mark all as read')) . '</button></form>']); ?>
<div class="card">
    <div class="list-group list-group-flush">
        <?php foreach ($p['rows'] as $n): ?>
            <div class="list-group-item d-flex gap-3 align-items-start <?= $n['is_read'] ? '' : 'bg-primary-subtle bg-opacity-25' ?>">
                <span class="feed-icon"><i class="bi <?= ['fee' => 'bi-cash-coin', 'exam' => 'bi-trophy', 'holiday' => 'bi-sun', 'attendance' => 'bi-calendar-x', 'homework' => 'bi-journal-text'][$n['type']] ?? 'bi-bell' ?>"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <a href="<?= e(url('notifications/open', ['id' => $n['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($n['title']) ?></a>
                    <div class="text-muted small"><?= nl2br(e($n['message'])) ?></div>
                    <div class="small text-muted"><?= e(NotificationsController::ago($n['created_at'])) ?></div>
                </div>
                <button class="btn btn-sm btn-link text-danger" data-post="<?= e(url('notifications/delete')) ?>" data-field-id="<?= (int)$n['id'] ?>" data-remove-row><i class="bi bi-x-lg"></i></button>
            </div>
        <?php endforeach; ?>
        <?php if (!$p['rows']): ?><div class="empty-state"><i class="bi bi-bell-slash"></i><?= e(t('No notifications yet.')) ?></div><?php endif; ?>
    </div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
