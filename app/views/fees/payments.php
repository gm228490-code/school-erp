<?php
$actions = '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . e(url_with(['print' => 1])) . '"><i class="bi bi-printer"></i> ' . e(t('Print / PDF')) . '</a>'
    . '<a class="btn btn-sm btn-outline-success" href="' . e(url_with(['export' => 'xls'])) . '"><i class="bi bi-file-earmark-excel"></i> Excel</a>'
    . '<a class="btn btn-sm btn-outline-secondary" href="' . e(url_with(['export' => 'csv'])) . '">CSV</a>';
partial('page_head', ['title' => t('Fee Payments'), 'icon' => 'bi-wallet2', 'actions' => $actions, 'crumbs' => [t('Fees') => null, t('Payments') => null]]);
?>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card tone-green"><span class="stat-icon"><i class="bi bi-cash-stack"></i></span><div><div class="stat-label"><?= e(t('Total Collected')) ?></div><div class="stat-value"><?= e(money($sum['total'])) ?></div><div class="stat-sub"><?= (int)$sum['n'] ?> <?= e(t('receipts')) ?></div></div></div></div>
    <?php foreach (['cash' => ['bi-cash', 'blue'], 'bank' => ['bi-bank', 'cyan'], 'online' => ['bi-phone', 'violet']] as $m => [$ic, $tone]): ?>
        <div class="col-md-3"><div class="card stat-card tone-<?= $tone ?>"><span class="stat-icon"><i class="bi <?= $ic ?>"></i></span><div><div class="stat-label"><?= e(t(ucfirst($m))) ?></div><div class="stat-value"><?= e(money($byMethod[$m] ?? 0)) ?></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card">
    <form class="table-toolbar" method="get">
        <input type="hidden" name="r" value="fees/payments">
        <input type="search" name="q" class="form-control form-control-sm" style="max-width:220px" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Receipt, student…')) ?>">
        <input type="date" name="from" class="form-control form-control-sm" style="max-width:160px" value="<?= e($from) ?>">
        <input type="date" name="to" class="form-control form-control-sm" style="max-width:160px" value="<?= e($to) ?>">
        <select name="method" class="form-select form-select-sm"><?= options(['cash' => t('Cash'), 'bank' => t('Bank'), 'cheque' => t('Cheque'), 'online' => t('Online')], input('method', ''), t('All Methods')) ?></select>
        <select name="class_id" class="form-select form-select-sm"><?= options(class_options(), input_int('class_id'), t('All Classes')) ?></select>
        <select name="received_by" class="form-select form-select-sm"><?= options(DB::pairs('SELECT DISTINCT u.id, u.name FROM fee_payments p JOIN users u ON u.id=p.received_by ORDER BY u.name'), input_int('received_by'), t('All Cashiers')) ?></select>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> <?= e(t('Filter')) ?></button>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th><?= e(t('Receipt')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Student')) ?></th><th><?= e(t('Invoice')) ?></th><th><?= e(t('Method')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th><th><?= e(t('Received By')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($p['rows'] as $r): ?>
                <tr><td class="fw-semibold"><?= e($r['receipt_no']) ?></td><td class="small"><?= e(fmt_date($r['paid_on'])) ?></td>
                    <td><?= e(student_name($r)) ?><div class="small text-muted"><?= e($r['reg_no'] . ' · ' . $r['class_name']) ?></div></td>
                    <td class="small"><a href="<?= e(url('fees/invoice', ['id' => $r['invoice_id']])) ?>"><?= e($r['invoice_no']) ?></a><div class="text-muted"><?= e($r['inv_title']) ?></div></td>
                    <td class="small"><?= e(t(ucfirst($r['method']))) ?><?= $r['reference'] ? '<div class="text-muted">' . e($r['reference']) . '</div>' : '' ?></td>
                    <td class="text-end fw-semibold"><?= e(money($r['amount'])) ?></td><td class="small"><?= e($r['by_name']) ?></td>
                    <td class="table-actions"><a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('fees/receipt', ['id' => $r['id']])) ?>"><i class="bi bi-printer"></i></a>
                        <?php if (can('fees.delete')): ?><button class="btn btn-sm btn-outline-danger" data-post="<?= e(url('fees/cancel_payment', ['id' => $r['id']])) ?>" data-confirm-text="<?= e(t('Reverse this payment? The invoice balance will be restored.')) ?>"><i class="bi bi-arrow-counterclockwise"></i></button><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$p['rows']): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-wallet2"></i><?= e(t('No payments in this period.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
