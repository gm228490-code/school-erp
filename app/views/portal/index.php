<?php $icon = 'bi-house'; include __DIR__ . '/_head.php';
$attT = array_sum(array_map('intval', $att)); $attP = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0); ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><a href="<?= e(url('portal/attendance')) ?>" class="card stat-card tone-green"><span class="stat-icon"><i class="bi bi-calendar-check"></i></span><div><div class="stat-label"><?= e(t('Attendance')) ?></div><div class="stat-value"><?= $attT ? round($attP / $attT * 100, 1) : 0 ?>%</div><div class="stat-sub"><?= e(t('Absent')) ?>: <?= (int)($att['absent'] ?? 0) ?></div></div></a></div>
    <div class="col-6 col-lg-3"><a href="<?= e(url('portal/fees')) ?>" class="card stat-card tone-<?= $due > 0 ? 'amber' : 'green' ?>"><span class="stat-icon"><i class="bi bi-cash-coin"></i></span><div><div class="stat-label"><?= e(t('Fee Due')) ?></div><div class="stat-value"><?= e(money($due)) ?></div></div></a></div>
    <div class="col-6 col-lg-3"><a href="<?= e(url('portal/results')) ?>" class="card stat-card tone-violet"><span class="stat-icon"><i class="bi bi-trophy"></i></span><div><div class="stat-label"><?= e(t('Last Result')) ?></div><div class="stat-value"><?= $lastResult ? e($lastResult['percentage']) . '%' : '-' ?></div><div class="stat-sub"><?= $lastResult ? e($lastResult['exam_name']) . ' · ' . e($lastResult['grade']) : '' ?></div></div></a></div>
    <div class="col-6 col-lg-3"><a href="<?= e(url('portal/homework')) ?>" class="card stat-card tone-blue"><span class="stat-icon"><i class="bi bi-journal-text"></i></span><div><div class="stat-label"><?= e(t('Open Homework')) ?></div><div class="stat-value"><?= count($homework) ?></div></div></a></div>
</div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><i class="bi bi-calendar3-week me-1"></i><?= e(t("Today's Classes")) ?></div><div class="card-body list-feed">
        <?php $any = false; foreach ($today as $p): if (!$p['subject'] && !$p['is_break']) continue; $any = true; ?>
            <div class="feed-item"><span class="feed-icon"><i class="bi <?= $p['is_break'] ? 'bi-cup-hot' : 'bi-book' ?>"></i></span><div><div class="fw-semibold"><?= e($p['is_break'] ? $p['period'] : $p['subject']) ?></div><div class="small text-muted"><?= e(fmt_time($p['start_time'])) ?> – <?= e(fmt_time($p['end_time'])) ?> <?= $p['teacher'] ? '· ' . e($p['teacher']) : '' ?></div></div></div>
        <?php endforeach; ?>
        <?php if (!$any): ?><div class="text-muted small"><?= e(t('No classes scheduled today.')) ?></div><?php endif; ?>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><i class="bi bi-journal-text me-1"></i><?= e(t('Upcoming Homework')) ?></div><div class="card-body list-feed">
        <?php foreach ($homework as $h): ?><div class="feed-item"><span class="feed-icon"><i class="bi bi-pencil"></i></span><div><div class="fw-semibold"><?= e($h['title']) ?></div><div class="small text-muted"><?= e($h['subject']) ?> · <?= e(t('Due')) ?> <?= e(fmt_date($h['due_date'])) ?> <?= $h['sub_status'] ? status_badge($h['sub_status']) : '' ?></div></div></div><?php endforeach; ?>
        <?php if (!$homework): ?><div class="text-muted small"><?= e(t('No pending homework.')) ?></div><?php endif; ?>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><i class="bi bi-megaphone me-1"></i><?= e(t('Announcements')) ?></div><div class="card-body list-feed">
        <?php foreach ($notices as $n): ?><div class="feed-item"><span class="feed-icon"><i class="bi bi-megaphone"></i></span><div><div class="fw-semibold"><?= e($n['title']) ?></div><div class="small text-muted"><?= e(fmt_date($n['publish_date'])) ?></div></div></div><?php endforeach; ?>
        <?php if (!$notices): ?><div class="text-muted small"><?= e(t('No announcements.')) ?></div><?php endif; ?>
    </div></div></div>
</div>
