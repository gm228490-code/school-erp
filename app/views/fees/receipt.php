<?php $copies = array_slice([t('Parent Copy'), t('School Copy'), t('Office Copy')], 0, max(1, min(3, (int)setting('receipt_copies', '2'))));
if (!empty($_GET['back']) && !Auth::isPortal()): ?>
<div class="no-print mb-2"><a href="<?= e(url('fees/collect')) ?>" class="btn btn-sm btn-success"><i class="bi bi-arrow-left"></i> <?= e(t('Collect another payment')) ?></a></div>
<?php endif; ?>
<?php foreach ($rows as $r): ?>
<div class="row g-3 mb-3">
<?php foreach ($copies as $copy): ?>
    <div class="col">
        <div class="receipt-copy" style="position:relative">
            <div class="watermark-paid">PAID</div>
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex gap-2 align-items-center">
                    <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" style="width:40px;height:40px;object-fit:contain" alt=""><?php endif; ?>
                    <div><div class="fw-bold" style="font-size:14px;color:#1e3a8a"><?= e(setting('school_name')) ?></div><div style="font-size:10px"><?= e(setting('school_address')) ?> · <?= e(setting('school_phone')) ?></div></div>
                </div>
                <span class="copy-label"><?= e($copy) ?></span>
            </div>
            <div class="text-center fw-bold my-2" style="letter-spacing:2px"><?= e(t('FEE RECEIPT')) ?></div>
            <table class="table table-sm table-borderless mb-1" style="font-size:11.5px">
                <tr><td><b><?= e(t('Receipt No')) ?>:</b> <?= e($r['receipt_no']) ?></td><td class="text-end"><b><?= e(t('Date')) ?>:</b> <?= e(fmt_date($r['paid_on'])) ?></td></tr>
                <tr><td><b><?= e(t('Student')) ?>:</b> <?= e(student_name($r)) ?></td><td class="text-end"><b><?= e(t('Reg No')) ?>:</b> <?= e($r['reg_no']) ?></td></tr>
                <tr><td><b><?= e(t('Father')) ?>:</b> <?= e($r['father_name']) ?></td><td class="text-end"><b><?= e(t('Class')) ?>:</b> <?= e($r['class_name'] . ' ' . $r['section_name']) ?></td></tr>
                <tr><td colspan="2"><b><?= e(t('Invoice')) ?>:</b> <?= e($r['invoice_no']) ?> — <?= e($r['inv_title']) ?></td></tr>
            </table>
            <table class="table table-bordered table-sm mb-1">
                <?php foreach ($r['items'] as $it): ?><tr><td><?= e($it['description']) ?></td><td class="text-end"><?= e(money($it['amount'] - $it['discount'], false)) ?></td></tr><?php endforeach; ?>
                <?php if ((float)$r['fine']): ?><tr><td><?= e(t('Late Fine')) ?></td><td class="text-end"><?= e(money($r['fine'], false)) ?></td></tr><?php endif; ?>
                <tr><th><?= e(t('Invoice Total')) ?></th><th class="text-end"><?= e(money($r['net_amount'], false)) ?></th></tr>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div style="font-size:11px"><?= e(t('Method')) ?>: <?= e(t(ucfirst($r['method']))) ?><?= $r['reference'] ? ' (' . e($r['reference']) . ')' : '' ?><br><?= e(t('Balance after payment')) ?>: <b><?= e(money(max(0, $r['net_amount'] - $r['inv_paid']))) ?></b></div>
                <div class="amount-box"><?= e(t('Paid')) ?>: <?= e(money($r['amount'])) ?></div>
            </div>
            <div class="d-flex justify-content-between mt-3" style="font-size:10px"><span><?= e(t('Received by')) ?>: <?= e($r['by_name']) ?></span><span>____________<br><?= e(t('Signature')) ?></span></div>
            <div style="font-size:9.5px" class="text-muted mt-1"><?= e(setting('receipt_note')) ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
