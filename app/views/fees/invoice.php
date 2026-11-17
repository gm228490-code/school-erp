<?php
$bal = FeeService::balance($inv);
$actions = '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . e(url('fees/invoice', ['id' => $inv['id'], 'print' => 1])) . '"><i class="bi bi-printer"></i> ' . e(t('Print Challan')) . '</a>';
if ($bal > 0 && $inv['status'] !== 'cancelled' && can('fees.collect')) {
    $actions .= '<a class="btn btn-sm btn-success" href="' . e(url('fees/collect', ['student_id' => $inv['student_id']])) . '"><i class="bi bi-cash-coin"></i> ' . e(t('Collect Payment')) . '</a>';
}
if (can('fees.delete') && $inv['status'] !== 'cancelled' && (float)$inv['paid_amount'] == 0) {
    $actions .= '<button class="btn btn-sm btn-outline-danger" data-post="' . e(url('fees/cancel', ['id' => $inv['id']])) . '" data-confirm-text="' . e(t('Cancel this invoice?')) . '"><i class="bi bi-x-circle"></i> ' . e(t('Cancel')) . '</button>';
}
partial('page_head', ['title' => t('Invoice') . ' ' . $inv['invoice_no'], 'icon' => 'bi-receipt', 'actions' => $actions, 'crumbs' => [t('Fees') => url('fees'), $inv['invoice_no'] => null]]);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                    <div><div class="text-muted small"><?= e(t('Billed to')) ?></div><a class="fw-bold fs-5" href="<?= e(url('students/show', ['id' => $inv['student_id']])) ?>"><?= e(student_name($inv)) ?></a>
                        <div class="small text-muted"><?= e($inv['reg_no']) ?> · <?= e($inv['class_name'] . ' ' . $inv['section_name']) ?> · <?= e(t('Father')) ?>: <?= e($inv['father_name']) ?></div></div>
                    <div class="text-end"><?= status_badge($inv['status']) ?><div class="small mt-1"><?= e(t('Issued')) ?>: <?= e(fmt_date($inv['issue_date'])) ?></div><div class="small"><?= e(t('Due')) ?>: <b><?= e(fmt_date($inv['due_date'])) ?></b></div></div>
                </div>
                <h6 class="fw-semibold"><?= e($inv['title']) ?></h6>
                <table class="table">
                    <thead><tr><th>#</th><th><?= e(t('Description')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th><th class="text-end"><?= e(t('Discount')) ?></th><th class="text-end"><?= e(t('Net')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $n => $it): ?><tr><td><?= $n + 1 ?></td><td><?= e($it['description']) ?></td><td class="text-end"><?= e(money($it['amount'])) ?></td><td class="text-end"><?= (float)$it['discount'] ? e(money($it['discount'])) : '-' ?></td><td class="text-end"><?= e(money($it['amount'] - $it['discount'])) ?></td></tr><?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><th colspan="4" class="text-end"><?= e(t('Gross')) ?></th><th class="text-end"><?= e(money($inv['gross_amount'])) ?></th></tr>
                        <tr><th colspan="4" class="text-end"><?= e(t('Discount / Scholarship')) ?></th><th class="text-end">- <?= e(money($inv['discount'])) ?></th></tr>
                        <tr><th colspan="4" class="text-end"><?= e(t('Late Fine')) ?></th><th class="text-end"><?= e(money($inv['fine'])) ?></th></tr>
                        <tr class="fs-6"><th colspan="4" class="text-end"><?= e(t('Net Payable')) ?></th><th class="text-end"><?= e(money($inv['net_amount'])) ?></th></tr>
                        <tr><th colspan="4" class="text-end text-success"><?= e(t('Paid')) ?></th><th class="text-end text-success"><?= e(money($inv['paid_amount'])) ?></th></tr>
                        <tr class="fs-5"><th colspan="4" class="text-end"><?= e(t('Balance')) ?></th><th class="text-end text-danger"><?= e(money($inv['status'] === 'cancelled' ? 0 : $bal)) ?></th></tr>
                    </tfoot>
                </table>
                <?php if ($fine > 0): ?><div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> <?= e(t('Overdue. Applicable late fine if paid today')) ?>: <b><?= e(money($fine)) ?></b></div><?php endif; ?>
                <?php if ($inv['remarks']): ?><div class="small text-muted mt-2"><?= e($inv['remarks']) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?= e(t('Payments')) ?></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($payments as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><a target="_blank" href="<?= e(url('fees/receipt', ['id' => $p['id']])) ?>" class="fw-semibold"><?= e($p['receipt_no']) ?></a><div class="small text-muted"><?= e(fmt_date($p['paid_on'])) ?> · <?= e(t(ucfirst($p['method']))) ?> · <?= e($p['by_name']) ?></div></div>
                        <div class="text-end"><div class="fw-semibold"><?= e(money($p['amount'])) ?></div>
                            <?php if (can('fees.delete')): ?><button class="btn btn-sm btn-link text-danger p-0 small" data-post="<?= e(url('fees/cancel_payment', ['id' => $p['id']])) ?>" data-confirm-text="<?= e(t('Reverse this payment? The invoice balance will be restored.')) ?>"><?= e(t('Reverse')) ?></button><?php endif; ?></div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$payments): ?><li class="list-group-item text-muted small"><?= e(t('No payments yet.')) ?></li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>
