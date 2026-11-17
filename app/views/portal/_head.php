<?php /** Child switcher header for portal pages. Needs $s, $children, $title, $icon */ ?>
<div class="page-head">
    <div><h1><span class="ph-icon"><i class="bi <?= e($icon ?? 'bi-house') ?>"></i></span><?= e($title) ?></h1>
        <div class="text-muted small"><?= e(student_name($s)) ?> · <?= e($s['reg_no']) ?> · <?= e($s['class_name'] . ' ' . $s['section_name']) ?></div></div>
    <?php if (count($children) > 1): ?>
        <form method="get" class="d-flex gap-2 align-items-center"><input type="hidden" name="r" value="<?= e($_GET['r'] ?? 'portal') ?>">
            <span class="small text-muted"><?= e(t('Child')) ?>:</span>
            <select name="student_id" class="form-select form-select-sm" data-autosubmit>
                <?php foreach ($children as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e(student_name($c) . ' (' . $c['class_name'] . ')') ?></option><?php endforeach; ?>
            </select></form>
    <?php endif; ?>
</div>
