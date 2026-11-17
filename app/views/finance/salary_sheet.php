<table class="table table-bordered table-sm">
    <thead><tr><th>#</th><th><?= e(t('Emp No')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Type')) ?></th><th class="text-end"><?= e(t('Basic')) ?></th><th class="text-end"><?= e(t('Allowances')) ?></th><th class="text-end"><?= e(t('Bonus')) ?></th><th class="text-end"><?= e(t('Deductions')) ?></th><th class="text-end"><?= e(t('Net')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Signature')) ?></th></tr></thead>
    <tbody><?php $t = 0; foreach ($rows as $i => $r): $t += $r['net_salary']; ?>
        <tr><td><?= $i + 1 ?></td><td><?= e($r['emp_no']) ?></td><td><?= e($r['emp_name']) ?></td><td><?= e(ucfirst($r['emp_type'])) ?></td><td class="text-end"><?= e(money($r['basic'], false)) ?></td><td class="text-end"><?= e(money($r['allowances'], false)) ?></td><td class="text-end"><?= e(money($r['bonus'], false)) ?></td><td class="text-end"><?= e(money($r['deductions'], false)) ?></td><td class="text-end"><b><?= e(money($r['net_salary'], false)) ?></b></td><td><?= e(ucfirst($r['status'])) ?></td><td style="width:90px"></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr><th colspan="8" class="text-end"><?= e(t('Total')) ?></th><th class="text-end"><?= e(money($t)) ?></th><th colspan="2"></th></tr></tfoot>
</table>
<div class="sig-row"><div><?= e(t('Accountant')) ?></div><div><?= e(t('Principal')) ?></div></div>
