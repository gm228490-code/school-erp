<?php $icon = 'bi-calendar3-week'; include __DIR__ . '/_head.php'; ?>
<div class="card"><div class="table-responsive">
    <?php if ($grid): include APP_PATH . '/views/timetable/_grid.php'; else: ?><div class="empty-state"><i class="bi bi-calendar3"></i><?= e(t('Timetable not published yet.')) ?></div><?php endif; ?>
</div></div>
