<?php $in = 0; $out = 0; $bal = $opening; ?>
<table class="table table-sm table-bordered mb-0">
    <thead><tr><th><?= e(t('Ref')) ?></th><th><?= e(t('Particulars')) ?></th><th><?= e(t('Method')) ?></th><th class="text-end"><?= e(t('Receipts')) ?></th><th class="text-end"><?= e(t('Payments')) ?></th><th class="text-end"><?= e(t('Balance')) ?></th></tr></thead>
    <tbody>
        <tr class="fw-semibold"><td></td><td><?= e(t('Opening balance')) ?></td><td></td><td></td><td></td><td class="text-end"><?= e(money($opening)) ?></td></tr>
        <?php foreach ($entries as $en): if ($en['type'] === 'in') { $in += $en['amount']; $bal += $en['amount']; } else { $out += $en['amount']; $bal -= $en['amount']; } ?>
            <tr><td class="small"><?= e($en['ref']) ?></td><td><?= e($en['descr']) ?></td><td class="small"><?= e(ucfirst((string)$en['method'])) ?></td>
                <td class="text-end text-success"><?= $en['type'] === 'in' ? e(money($en['amount'], false)) : '' ?></td><td class="text-end text-danger"><?= $en['type'] === 'out' ? e(money($en['amount'], false)) : '' ?></td><td class="text-end"><?= e(money($bal, false)) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$entries): ?><tr><td colspan="6" class="text-center text-muted"><?= e(t('No transactions on this date.')) ?></td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr><th colspan="3" class="text-end"><?= e(t('Totals / Closing balance')) ?></th><th class="text-end"><?= e(money($in)) ?></th><th class="text-end"><?= e(money($out)) ?></th><th class="text-end"><?= e(money($bal)) ?></th></tr></tfoot>
</table>
