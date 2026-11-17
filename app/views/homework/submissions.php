<?php partial('page_head', ['title' => $h['title'], 'icon' => 'bi-inbox', 'crumbs' => [t('Homework') => url('homework'), t('Submissions') => null]]); ?>
<div class="card mb-4"><div class="card-body">
    <div class="small text-muted mb-1"><?= e($h['class_name'] . ' ' . $h['section_name']) ?> · <?= e($h['subject']) ?> · <?= e(t('Due')) ?> <?= e(fmt_date($h['due_date'])) ?><?= $h['max_marks'] ? ' · ' . e(t('Max marks')) . ' ' . (float)$h['max_marks'] : '' ?></div>
    <div><?= nl2br(e($h['description'])) ?></div>
    <?php if ($h['attachment']): ?><a target="_blank" href="<?= e(upload_url($h['attachment'])) ?>"><i class="bi bi-paperclip"></i> <?= e(t('Attachment')) ?></a><?php endif; ?>
</div></div>
<form method="post" action="<?= e(url('homework/review')) ?>" class="card"><?= csrf_field() ?><input type="hidden" name="homework_id" value="<?= (int)$h['id'] ?>">
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th><?= e(t('Student')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Submission')) ?></th><?php if (can('homework.manage')): ?><th style="width:110px"><?= e(t('Marks')) ?></th><th><?= e(t('Feedback')) ?></th><th><?= e(t('Checked')) ?></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $st = $r['id'] ? $r['status'] : 'pending'; ?>
            <tr><td><?= e(student_name($r)) ?><div class="small text-muted"><?= e($r['reg_no']) ?> · <?= e(t('Roll')) ?> <?= e($r['roll_no']) ?></div></td>
                <td><?= status_badge($st) ?><?= $r['submitted_at'] ? '<div class="small text-muted">' . e(fmt_date($r['submitted_at'], 'd-M h:i A')) . '</div>' : '' ?></td>
                <td class="small"><?= e(mb_strimwidth((string)$r['content'], 0, 120, '…')) ?><?php if ($r['attachment']): ?> <a target="_blank" href="<?= e(upload_url($r['attachment'])) ?>"><i class="bi bi-paperclip"></i></a><?php endif; ?></td>
                <?php if (can('homework.manage')): ?>
                <td><input type="number" step="0.5" min="0" class="form-control form-control-sm" name="fb[<?= (int)$r['student_id'] ?>][marks]" value="<?= $r['marks'] !== null ? e((float)$r['marks']) : '' ?>"></td>
                <td><input class="form-control form-control-sm" name="fb[<?= (int)$r['student_id'] ?>][feedback]" value="<?= e($r['feedback']) ?>" maxlength="2000"></td>
                <td><input type="checkbox" class="form-check-input" name="fb[<?= (int)$r['student_id'] ?>][checked]" value="1" <?= $st === 'reviewed' ? 'checked' : '' ?>></td>
                <?php endif; ?></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php if (can('homework.manage')): ?><div class="card-body border-top text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Feedback')) ?></button></div><?php endif; ?>
</form>
