<?php $icon = 'bi-cash-coin'; include __DIR__ . '/_head.php';
$due = array_sum(array_map(fn($i) => in_array($i['status'], ['unpaid', 'partial'], true) ? $i['net_amount'] - $i['paid_amount'] : 0, $invoices)); ?>
<div class="alert <?= $due > 0 ? 'alert-warning' : 'alert-success' ?> d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><?= $due > 0 ? e(t('Outstanding balance')) . ': <b>' . e(money($due)) . '</b>' : e(t('All fees are paid. Thank you!')) ?></span>
    <a target="_blank" class="btn btn-sm btn-light" href="<?= e(url('fees/ledger', ['student_id' => $s['id']])) ?>"><i class="bi bi-journal-text"></i> <?= e(t('Fee Ledger')) ?></a>
</div>
<div class="card mb-4"><div class="card-header"><?= e(t('Invoices')) ?></div><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th><?= e(t('Invoice')) ?></th><th><?= e(t('Title')) ?></th><th><?= e(t('Due Date')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th><th class="text-end"><?= e(t('Paid')) ?></th><th class="text-end"><?= e(t('Balance')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead><tbody>
    <?php foreach ($invoices as $i): ?><tr><td><?= e($i['invoice_no']) ?></td><td><?= e($i['title']) ?></td><td><?= e(fmt_date($i['due_date'])) ?></td><td class="text-end"><?= e(money($i['net_amount'])) ?></td><td class="text-end"><?= e(money($i['paid_amount'])) ?></td><td class="text-end fw-semibold"><?= e(money($i['net_amount'] - $i['paid_amount'])) ?></td><td><?= status_badge($i['status']) ?></td>
        <td><?php if ($i['status'] !== 'paid'): ?><a target="_blank" class="btn btn-sm btn-light-soft" href="<?= e(url('portal/challan', ['id' => $i['id']])) ?>"><i class="bi bi-printer"></i> <?= e(t('Challan')) ?></a><?php endif; ?></td></tr><?php endforeach; ?>
    <?php if (!$invoices): ?><tr><td colspan="8" class="text-muted"><?= e(t('No invoices.')) ?></td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="card"><div class="card-header"><?= e(t('Payment History & Receipts')) ?></div><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th><?= e(t('Receipt')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('For')) ?></th><th><?= e(t('Method')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th><th></th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr><td><?= e($p['receipt_no']) ?></td><td><?= e(fmt_date($p['paid_on'])) ?></td><td><?= e($p['title']) ?></td><td><?= e(t(ucfirst($p['method']))) ?></td><td class="text-end fw-semibold"><?= e(money($p['amount'])) ?></td>
        <td><a target="_blank" class="btn btn-sm btn-outline-primary" href="<?= e(url('fees/receipt', ['id' => $p['id']])) ?>"><i class="bi bi-download"></i> <?= e(t('Receipt')) ?></a></td></tr><?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="6" class="text-muted"><?= e(t('No payments yet.')) ?></td></tr><?php endif; ?>
</tbody></table></div></div>
