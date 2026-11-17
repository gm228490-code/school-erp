<?php
$actions = '<a class="btn btn-sm btn-outline-success" href="' . e(url_with(['export' => 'xls'])) . '"><i class="bi bi-file-earmark-excel"></i> Excel</a>'
    . '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . e(url('fees/challans', ['month' => input('month'), 'class_id' => input_int('class_id')])) . '"><i class="bi bi-printer"></i> ' . e(t('Print Unpaid Challans')) . '</a>';
if (can('fees.manage')) {
    $actions .= '<button class="btn btn-sm btn-primary" data-modal="' . e(url('fees/create_form')) . '"><i class="bi bi-plus-lg"></i> ' . e(t('New Invoice')) . '</button>';
}
partial('page_head', ['title' => t('Fee Invoices'), 'icon' => 'bi-receipt', 'actions' => $actions, 'crumbs' => [t('Fees') => null, t('Invoices') => null]]);
?>
<div class="card">
    <form class="table-toolbar" method="get">
        <input type="hidden" name="r" value="fees">
        <div class="input-group input-group-sm" style="max-width:260px"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Invoice, Reg No, name…')) ?>"></div>
        <input type="month" name="month" class="form-control form-control-sm" style="max-width:170px" value="<?= e($_GET['month'] ?? '') ?>" data-autosubmit>
        <select name="class_id" class="form-select form-select-sm" data-autosubmit><?= options(class_options(), input_int('class_id'), t('All Classes')) ?></select>
        <select name="status" class="form-select form-select-sm" data-autosubmit><?= options(['due' => t('Unpaid + Partial'), 'overdue' => t('Overdue'), 'unpaid' => t('Unpaid'), 'partial' => t('Partial'), 'paid' => t('Paid'), 'cancelled' => t('Cancelled')], input('status', ''), t('All Statuses')) ?></select>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
        <div class="ms-auto small fw-semibold"><?= e(t('Billed')) ?>: <?= e(money($totals['net'])) ?> · <?= e(t('Collected')) ?>: <span class="text-success"><?= e(money($totals['paid'])) ?></span> · <?= e(t('Balance')) ?>: <span class="text-danger"><?= e(money($totals['net'] - $totals['paid'])) ?></span></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th><?= sort_link('no', t('Invoice')) ?></th><th><?= sort_link('student', t('Student')) ?></th><th><?= e(t('Title')) ?></th><th><?= sort_link('due', t('Due Date')) ?></th><th class="text-end"><?= sort_link('net', t('Net')) ?></th><th class="text-end"><?= e(t('Paid')) ?></th><th class="text-end"><?= e(t('Balance')) ?></th><th><?= e(t('Status')) ?></th><th class="text-end"><?= e(t('Actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($p['rows'] as $i): $bal = $i['status'] === 'cancelled' ? 0 : $i['net_amount'] - $i['paid_amount']; ?>
                <tr>
                    <td class="fw-semibold"><a href="<?= e(url('fees/invoice', ['id' => $i['id']])) ?>"><?= e($i['invoice_no']) ?></a></td>
                    <td><?= e(student_name($i)) ?><div class="small text-muted"><?= e($i['reg_no'] . ' · ' . $i['class_name'] . ' ' . $i['section_name']) ?></div></td>
                    <td class="small"><?= e($i['title']) ?></td>
                    <td class="small <?= $bal > 0 && $i['due_date'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>"><?= e(fmt_date($i['due_date'])) ?></td>
                    <td class="text-end"><?= e(money($i['net_amount'])) ?></td><td class="text-end text-success"><?= e(money($i['paid_amount'])) ?></td><td class="text-end fw-semibold"><?= e(money($bal)) ?></td>
                    <td><?= status_badge($i['status']) ?></td>
                    <td class="table-actions">
                        <?php if ($bal > 0 && can('fees.collect')): ?><a class="btn btn-sm btn-success" href="<?= e(url('fees/collect', ['student_id' => $i['student_id']])) ?>" title="<?= e(t('Collect')) ?>"><i class="bi bi-cash-coin"></i></a><?php endif; ?>
                        <a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('fees/invoice', ['id' => $i['id'], 'print' => 1])) ?>" title="<?= e(t('Print')) ?>"><i class="bi bi-printer"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$p['rows']): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-receipt"></i><?= e(t('No invoices found.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
