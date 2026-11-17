<?php
$actions = '<a target="_blank" class="btn btn-sm btn-outline-secondary" href="' . e(url_with(['print' => 1])) . '"><i class="bi bi-printer"></i> ' . e(t('Salary Sheet')) . '</a>'
    . '<a class="btn btn-sm btn-outline-success" href="' . e(url_with(['export' => 'xls'])) . '"><i class="bi bi-file-earmark-excel"></i> Excel</a>';
partial('page_head', ['title' => t('Salaries') . ' — ' . fmt_month($month), 'icon' => 'bi-cash-stack', 'actions' => $actions, 'crumbs' => [t('Accounts') => null, t('Salaries') => null]]);
$tot = array_sum(array_column($rows, 'net_salary'));
$paid = array_sum(array_map(fn($r) => $r['status'] === 'paid' ? $r['net_salary'] : 0, $rows));
?>
<div class="row g-3 mb-4">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <form method="get" class="d-flex gap-2 align-items-end mb-3"><input type="hidden" name="r" value="finance/salaries">
            <div class="flex-grow-1"><label class="form-label"><?= e(t('Month')) ?></label><input type="month" name="month" value="<?= e($month) ?>" class="form-control" data-autosubmit></div></form>
        <form method="post" action="<?= e(url('finance/generate_salaries')) ?>"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>">
            <label class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="deduct_absent" value="1"> <?= e(t('Deduct absent days (basic ÷ 30 per day)')) ?></label>
            <button class="btn btn-primary w-100" <?= $pending ? '' : 'disabled' ?>><i class="bi bi-lightning-charge"></i> <?= e(t('Generate payroll')) ?> (<?= $pending ?> <?= e(t('pending')) ?>)</button></form>
    </div></div></div>
    <div class="col-lg-7"><div class="row g-3 h-100">
        <div class="col-6"><div class="card stat-card tone-blue"><span class="stat-icon"><i class="bi bi-calculator"></i></span><div><div class="stat-label"><?= e(t('Total Payroll')) ?></div><div class="stat-value"><?= e(money($tot)) ?></div><div class="stat-sub"><?= count($rows) ?> <?= e(t('employees')) ?></div></div></div></div>
        <div class="col-6"><div class="card stat-card tone-green"><span class="stat-icon"><i class="bi bi-check2-circle"></i></span><div><div class="stat-label"><?= e(t('Paid')) ?></div><div class="stat-value"><?= e(money($paid)) ?></div><div class="stat-sub"><?= e(t('Unpaid')) ?>: <?= e(money($tot - $paid)) ?></div></div></div></div>
    </div></div>
</div>
<div class="card">
    <?php if ($tot - $paid > 0): ?>
    <form method="post" action="<?= e(url('finance/pay_all')) ?>" class="table-toolbar" data-confirm="<?= e(t('Mark all unpaid salaries of this month as paid?')) ?>"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>">
        <span class="small fw-semibold"><?= e(t('Pay all unpaid')) ?>:</span>
        <select name="method" class="form-select form-select-sm"><?= options(['bank' => t('Bank'), 'cash' => t('Cash'), 'cheque' => t('Cheque'), 'online' => t('Online')], 'bank') ?></select>
        <input type="date" name="paid_on" class="form-control form-control-sm" style="max-width:160px" value="<?= date('Y-m-d') ?>">
        <button class="btn btn-sm btn-success"><i class="bi bi-check2-all"></i> <?= e(t('Pay All')) ?></button>
    </form>
    <?php endif; ?>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th><?= e(t('Slip')) ?></th><th><?= e(t('Employee')) ?></th><th class="text-end"><?= e(t('Basic')) ?></th><th class="text-end"><?= e(t('Allowances')) ?></th><th class="text-end"><?= e(t('Bonus')) ?></th><th class="text-end"><?= e(t('Deductions')) ?></th><th class="text-end"><?= e(t('Net')) ?></th><th><?= e(t('Status')) ?></th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td class="small"><?= e($r['slip_no']) ?></td><td><div class="fw-semibold"><?= e($r['emp_name']) ?></div><div class="small text-muted"><?= e($r['emp_no']) ?> · <?= e(t(ucfirst($r['emp_type']))) ?></div></td>
                <td class="text-end"><?= e(money($r['basic'], false)) ?></td><td class="text-end"><?= e(money($r['allowances'], false)) ?></td><td class="text-end"><?= e(money($r['bonus'], false)) ?></td><td class="text-end text-danger"><?= e(money($r['deductions'], false)) ?></td>
                <td class="text-end fw-semibold"><?= e(money($r['net_salary'])) ?></td><td><?= status_badge($r['status']) ?><?= $r['paid_on'] ? '<div class="small text-muted">' . e(fmt_date($r['paid_on'])) . '</div>' : '' ?></td>
                <td class="table-actions"><button class="btn btn-sm btn-light-soft" data-modal="<?= e(url('finance/salary_form', ['id' => $r['id']])) ?>" data-size="modal-md"><i class="bi bi-<?= $r['status'] === 'paid' ? 'pencil' : 'cash' ?>"></i></button>
                    <a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('finance/slip', ['id' => $r['id']])) ?>"><i class="bi bi-printer"></i></a>
                    <?php if ($r['status'] !== 'paid'): ?><button class="btn btn-sm btn-outline-danger" data-post="<?= e(url('finance/delete_salary', ['id' => $r['id']])) ?>" data-remove-row><i class="bi bi-trash"></i></button><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-cash-stack"></i><?= e(t('No payroll for this month. Click Generate payroll.')) ?></div></td></tr><?php endif; ?>
        </tbody></table></div>
</div>
