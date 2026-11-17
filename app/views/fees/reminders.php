<?php partial('page_head', ['title' => t('Fee Due Alerts'), 'icon' => 'bi-bell', 'crumbs' => [t('Communication') => null, t('Fee Due Alerts') => null]]); ?>
<form method="post" class="card" data-confirm="<?= e(t('Send reminders to the selected parents?')) ?>">
    <?= csrf_field() ?>
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><?= count($rows) ?> <?= e(t('students with overdue fees')) ?> · <?= e(t('Total')) ?>: <b><?= e(money(array_sum(array_column($rows, 'due')))) ?></b></span>
        <div class="d-flex gap-3 align-items-center">
            <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="sms" value="1"> <?= e(t('Also send SMS / Email')) ?> <?= setting('sms_enabled') === '1' || setting('email_enabled') === '1' ? '' : '<span class="small text-muted">(' . e(t('not configured')) . ')</span>' ?></label>
            <button class="btn btn-primary btn-sm"><i class="bi bi-send"></i> <?= e(t('Send Reminders')) ?></button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th><input type="checkbox" class="form-check-input" data-check-all=".chk-rem" checked></th><th><?= e(t('Student')) ?></th><th><?= e(t('Class')) ?></th><th><?= e(t('Father')) ?></th><th><?= e(t('Phone')) ?></th><th><?= e(t('Invoices')) ?></th><th><?= e(t('Overdue Since')) ?></th><th class="text-end"><?= e(t('Amount Due')) ?></th><th><?= e(t('Portal')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr><td><input type="checkbox" class="form-check-input chk-rem" name="students[]" value="<?= (int)$r['id'] ?>" checked></td>
                    <td><a href="<?= e(url('fees/collect', ['student_id' => $r['id']])) ?>"><?= e(student_name($r)) ?></a><div class="small text-muted"><?= e($r['reg_no']) ?></div></td>
                    <td><?= e($r['class_name']) ?></td><td><?= e($r['father_name']) ?></td><td><?= e($r['father_phone']) ?></td><td><?= (int)$r['invoices'] ?></td>
                    <td class="text-danger small"><?= e(fmt_date($r['oldest'])) ?></td><td class="text-end fw-semibold"><?= e(money($r['due'])) ?></td>
                    <td><?= $r['user_id'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-dash text-muted"></i>' ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-emoji-smile"></i><?= e(t('No overdue fees.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</form>
