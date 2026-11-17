<?php /** @var array $inv @var array $items @var string $copy */
$bal = FeeService::balance($inv);
$fine = FeeService::lateFine($inv, $inv['due_date'] < date('Y-m-d') ? date('Y-m-d') : date('Y-m-d', strtotime($inv['due_date'] . ' +1 day'))); ?>
<div class="receipt-copy" style="position:relative">
    <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex gap-2 align-items-center">
            <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" style="width:38px;height:38px;object-fit:contain" alt=""><?php endif; ?>
            <div><div class="fw-bold" style="font-size:13px;color:#1e3a8a"><?= e(setting('school_name')) ?></div><div style="font-size:10px"><?= e(setting('school_address')) ?> <?= e(setting('school_phone')) ?></div></div>
        </div>
        <span class="copy-label"><?= e($copy) ?></span>
    </div>
    <div class="d-flex justify-content-between mt-2" style="font-size:11.5px">
        <div><b><?= e(t('Invoice')) ?>:</b> <?= e($inv['invoice_no']) ?><br><b><?= e(t('Student')) ?>:</b> <?= e(student_name($inv)) ?> (<?= e($inv['reg_no']) ?>)<br><b><?= e(t('Father')) ?>:</b> <?= e($inv['father_name']) ?></div>
        <div class="text-end"><b><?= e(t('Class')) ?>:</b> <?= e($inv['class_name'] . ' ' . $inv['section_name']) ?><br><b><?= e(t('Issue')) ?>:</b> <?= e(fmt_date($inv['issue_date'])) ?><br><b><?= e(t('Due Date')) ?>:</b> <?= e(fmt_date($inv['due_date'])) ?></div>
    </div>
    <div class="fw-semibold mt-1" style="font-size:12px"><?= e($inv['title']) ?></div>
    <table class="table table-bordered table-sm mt-1 mb-1">
        <tr><th><?= e(t('Description')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th></tr>
        <?php foreach ($items as $it): ?><tr><td><?= e($it['description']) ?><?= (float)$it['discount'] ? ' <small>(-' . e(money($it['discount'], false)) . ')</small>' : '' ?></td><td class="text-end"><?= e(money($it['amount'] - $it['discount'], false)) ?></td></tr><?php endforeach; ?>
        <?php if ((float)$inv['fine']): ?><tr><td><?= e(t('Late Fine')) ?></td><td class="text-end"><?= e(money($inv['fine'], false)) ?></td></tr><?php endif; ?>
        <?php if ((float)$inv['paid_amount']): ?><tr><td><?= e(t('Less: Already Paid')) ?></td><td class="text-end">-<?= e(money($inv['paid_amount'], false)) ?></td></tr><?php endif; ?>
        <tr><th><?= e(t('Payable by due date')) ?></th><th class="text-end"><?= e(money($bal)) ?></th></tr>
        <?php if ($fine > 0 || (float)setting('late_fine_amount', '0') > 0): ?><tr><td><?= e(t('Payable after due date')) ?></td><td class="text-end"><?= e(money($bal + max($fine, (float)setting('late_fine_amount', '0')))) ?></td></tr><?php endif; ?>
    </table>
    <div class="d-flex justify-content-between" style="font-size:10px"><span><?= setting('challan_bank') ? e(t('Bank')) . ': ' . e(setting('challan_bank')) : '' ?></span><span><?= e(t('Cashier / Bank stamp')) ?> ____________</span></div>
</div>
