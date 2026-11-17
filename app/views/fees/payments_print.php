<table class="table table-bordered table-sm">
    <thead><tr><th>#</th><th><?= e(t('Receipt')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Reg No')) ?></th><th><?= e(t('Student')) ?></th><th><?= e(t('Class')) ?></th><th><?= e(t('Invoice')) ?></th><th><?= e(t('Method')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th></tr></thead>
    <tbody>
    <?php $total = 0; $by = []; foreach ($rows as $n => $r): $total += $r['amount']; $by[$r['method']] = ($by[$r['method']] ?? 0) + $r['amount']; ?>
        <tr><td><?= $n + 1 ?></td><td><?= e($r['receipt_no']) ?></td><td><?= e(fmt_date($r['paid_on'])) ?></td><td><?= e($r['reg_no']) ?></td><td><?= e(student_name($r)) ?></td><td><?= e($r['class_name']) ?></td><td><?= e($r['invoice_no']) ?></td><td><?= e(ucfirst($r['method'])) ?></td><td class="text-end"><?= e(money($r['amount'], false)) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="8" class="text-end"><?= e(t('Total')) ?></th><th class="text-end"><?= e(money($total)) ?></th></tr></tfoot>
</table>
<p class="small"><?php foreach ($by as $m => $a): ?><?= e(ucfirst($m)) ?>: <b><?= e(money($a)) ?></b> &nbsp; <?php endforeach; ?></p>
<div class="sig-row"><div><?= e(t('Cashier')) ?></div><div><?= e(t('Accountant')) ?></div><div><?= e(t('Principal')) ?></div></div>
