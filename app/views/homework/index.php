<?php partial('page_head', ['title' => t('Homework & Assignments'), 'icon' => 'bi-journal-text', 'crumbs' => [t('Academics') => null, t('Homework') => null],
    'actions' => can('homework.manage') ? '<button class="btn btn-primary btn-sm" data-modal="' . e(url('homework/form')) . '"><i class="bi bi-plus-lg"></i> ' . e(t('New Homework')) . '</button>' : '']); ?>
<div class="card">
    <form class="table-toolbar" method="get"><input type="hidden" name="r" value="homework">
        <input type="search" name="q" class="form-control form-control-sm" style="max-width:220px" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Search…')) ?>">
        <select name="class_id" class="form-select form-select-sm" data-autosubmit><?= options(class_options(), input_int('class_id'), t('All Classes')) ?></select>
        <select name="subject_id" class="form-select form-select-sm" data-autosubmit><?= options(subject_options(), input_int('subject_id'), t('All Subjects')) ?></select>
        <label class="form-check small mb-0"><input type="checkbox" class="form-check-input" name="active" value="1" <?= input('active') ? 'checked' : '' ?> data-autosubmit> <?= e(t('Only open')) ?></label>
    </form>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th><?= e(t('Title')) ?></th><th><?= e(t('Class')) ?></th><th><?= e(t('Subject')) ?></th><th><?= e(t('Teacher')) ?></th><th><?= e(t('Assigned')) ?></th><th><?= e(t('Due')) ?></th><th><?= e(t('Submitted')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($p['rows'] as $h): ?>
            <tr><td><div class="fw-semibold"><?= e($h['title']) ?></div><?php if ($h['attachment']): ?><a class="small" target="_blank" href="<?= e(upload_url($h['attachment'])) ?>"><i class="bi bi-paperclip"></i> <?= e(t('Attachment')) ?></a><?php endif; ?></td>
                <td><?= e($h['class_name'] . ' ' . $h['section_name']) ?></td><td><?= e($h['subject']) ?></td><td class="small"><?= e($h['teacher']) ?></td>
                <td class="small"><?= e(fmt_date($h['assign_date'])) ?></td><td class="small <?= $h['due_date'] < date('Y-m-d') ? 'text-muted' : 'text-primary fw-semibold' ?>"><?= e(fmt_date($h['due_date'])) ?></td>
                <td><?= (int)$h['submitted'] ?>/<?= (int)$h['strength'] ?></td>
                <td class="table-actions"><a class="btn btn-sm btn-light-soft" href="<?= e(url('homework/submissions', ['id' => $h['id']])) ?>" title="<?= e(t('Submissions')) ?>"><i class="bi bi-inbox"></i></a>
                    <?php if (can('homework.manage')): ?><button class="btn btn-sm btn-light-soft" data-modal="<?= e(url('homework/form', ['id' => $h['id']])) ?>"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger" data-post="<?= e(url('homework/delete', ['id' => $h['id']])) ?>" data-remove-row><i class="bi bi-trash"></i></button><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$p['rows']): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-journal-text"></i><?= e(t('No homework yet.')) ?></div></td></tr><?php endif; ?>
        </tbody></table></div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
