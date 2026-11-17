<?php $icon = 'bi-journal-text'; include __DIR__ . '/_head.php'; ?>
<?php foreach ($rows as $h): $open = $h['due_date'] >= date('Y-m-d'); ?>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <div><h6 class="fw-bold mb-1"><?= e($h['title']) ?></h6><div class="small text-muted"><?= e($h['subject']) ?> · <?= e($h['teacher']) ?> · <?= e(t('Assigned')) ?> <?= e(fmt_date($h['assign_date'])) ?> · <b class="<?= $open ? 'text-primary' : '' ?>"><?= e(t('Due')) ?> <?= e(fmt_date($h['due_date'])) ?></b></div></div>
        <div><?= $h['sub_status'] ? status_badge($h['sub_status']) : status_badge($open ? 'pending' : 'absent') ?></div>
    </div>
    <?php if ($h['description']): ?><p class="mt-2 mb-1"><?= nl2br(e($h['description'])) ?></p><?php endif; ?>
    <?php if ($h['attachment']): ?><a target="_blank" href="<?= e(upload_url($h['attachment'])) ?>" class="small"><i class="bi bi-paperclip"></i> <?= e(t('Download attachment')) ?></a><?php endif; ?>
    <?php if ($h['sub_status'] === 'reviewed'): ?>
        <div class="alert alert-success py-2 mt-2 mb-0 small"><b><?= e(t('Teacher feedback')) ?>:</b> <?= e($h['feedback'] ?: '-') ?><?= $h['marks'] !== null ? ' · ' . e(t('Marks')) . ': <b>' . (float)$h['marks'] . ($h['max_marks'] ? '/' . (float)$h['max_marks'] : '') . '</b>' : '' ?></div>
    <?php else: ?>
        <details class="mt-2" <?= !$h['sub_status'] && $open ? 'open' : '' ?>><summary class="small text-primary"><?= e($h['sub_status'] ? t('Update submission') : t('Submit homework')) ?></summary>
            <form method="post" action="<?= e(url('portal/submit')) ?>" enctype="multipart/form-data" class="mt-2"><?= csrf_field() ?><input type="hidden" name="homework_id" value="<?= (int)$h['id'] ?>">
                <textarea name="content" class="form-control mb-2" rows="3" maxlength="5000" placeholder="<?= e(t('Write your answer…')) ?>"><?= e($h['content']) ?></textarea>
                <div class="d-flex gap-2 flex-wrap"><input type="file" name="attachment" class="form-control form-control-sm" style="max-width:320px" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-send"></i> <?= e(t('Submit')) ?></button></div>
                <?php if ($h['sub_file']): ?><div class="small mt-1"><a target="_blank" href="<?= e(upload_url($h['sub_file'])) ?>"><i class="bi bi-paperclip"></i> <?= e(t('Your file')) ?></a></div><?php endif; ?>
            </form></details>
    <?php endif; ?>
</div></div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="card"><div class="empty-state"><i class="bi bi-journal-check"></i><?= e(t('No homework assigned.')) ?></div></div><?php endif; ?>
