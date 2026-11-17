<table class="table table-bordered table-sm" style="max-width:640px;margin:auto">
    <tr><th colspan="2" style="background:#dbeafe"><?= e(t('INCOME')) ?></th></tr>
    <tr><td><?= e(t('Fee Collection')) ?></td><td class="text-end"><?= e(money($tot['fees'])) ?></td></tr>
    <?php foreach ($incCats as $c): ?><tr><td><?= e($c['name']) ?></td><td class="text-end"><?= e(money($c['total'])) ?></td></tr><?php endforeach; ?>
    <tr><th><?= e(t('Total Income')) ?></th><th class="text-end"><?= e(money($tot['income'])) ?></th></tr>
    <tr><th colspan="2" style="background:#fee2e2"><?= e(t('EXPENDITURE')) ?></th></tr>
    <tr><td><?= e(t('Salaries')) ?></td><td class="text-end"><?= e(money($tot['salaries'])) ?></td></tr>
    <?php foreach ($expCats as $c): ?><tr><td><?= e($c['name']) ?></td><td class="text-end"><?= e(money($c['total'])) ?></td></tr><?php endforeach; ?>
    <tr><th><?= e(t('Total Expenditure')) ?></th><th class="text-end"><?= e(money($tot['outflow'])) ?></th></tr>
    <tr><th style="font-size:14px"><?= e($tot['net'] >= 0 ? t('SURPLUS') : t('DEFICIT')) ?></th><th class="text-end" style="font-size:14px"><?= e(money(abs($tot['net']))) ?></th></tr>
</table>
<div class="sig-row"><div><?= e(t('Accountant')) ?></div><div><?= e(t('Principal')) ?></div></div>
