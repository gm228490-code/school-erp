<div class="kv-print">
    <b><?= e(t('Student')) ?>:</b><span><?= e(student_name($s)) ?></span><b><?= e(t('Reg No')) ?>:</b><span><?= e($s['reg_no']) ?></span>
    <b><?= e(t('Class')) ?>:</b><span><?= e($s['class_name'] . ' ' . $s['section_name']) ?></span><b><?= e(t('Father')) ?>:</b><span><?= e($s['father_name']) ?></span>
</div>
<table class="table table-bordered table-sm">
    <thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Reference')) ?></th><th><?= e(t('Description')) ?></th><th class="text-end"><?= e(t('Debit (Charged)')) ?></th><th class="text-end"><?= e(t('Credit (Paid)')) ?></th><th class="text-end"><?= e(t('Balance')) ?></th></tr></thead>
    <tbody>
    <?php $bal = 0; $dr = 0; $cr = 0; foreach ($entries as $en): $bal += $en['debit'] - $en['credit']; $dr += $en['debit']; $cr += $en['credit']; ?>
        <tr><td><?= e(fmt_date($en['date'])) ?></td><td><?= e($en['ref']) ?></td><td><?= e($en['desc']) ?></td><td class="text-end"><?= $en['debit'] ? e(money($en['debit'], false)) : '' ?></td><td class="text-end"><?= $en['credit'] ? e(money($en['credit'], false)) : '' ?></td><td class="text-end"><?= e(money($bal, false)) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$entries): ?><tr><td colspan="6" class="text-center"><?= e(t('No transactions.')) ?></td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr><th colspan="3" class="text-end"><?= e(t('Totals')) ?></th><th class="text-end"><?= e(money($dr, false)) ?></th><th class="text-end"><?= e(money($cr, false)) ?></th><th class="text-end"><?= e(money($bal)) ?></th></tr></tfoot>
</table>
<p class="small"><?= $bal > 0 ? e(t('Outstanding balance')) . ': <b>' . e(money($bal)) . '</b>' : e(t('No outstanding balance.')) ?></p>
