<form method="post" action="<?= e(url('finance/save_salary')) ?>" data-ajax novalidate>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
    <div class="modal-header"><h5 class="modal-title"><?= e($s['emp_name']) ?> · <?= e(fmt_month($s['salary_month'])) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <?= form_field('basic', ['label' => 'Basic', 'type' => 'decimal', 'rules' => 'required'], (float)$s['basic']) ?>
        <?= form_field('allowances', ['label' => 'Allowances', 'type' => 'decimal'], (float)$s['allowances']) ?>
        <?= form_field('bonus', ['label' => 'Bonus', 'type' => 'decimal'], (float)$s['bonus']) ?>
        <?= form_field('deductions', ['label' => 'Deductions', 'type' => 'decimal'], (float)$s['deductions']) ?>
        <?= form_field('remarks', ['label' => 'Remarks', 'col' => 12], $s['remarks']) ?>
        <?php if ($s['status'] !== 'paid'): ?>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pay_now" value="1" id="pn" checked><label class="form-check-label" for="pn"><?= e(t('Mark as paid now')) ?></label></div></div>
            <?= form_field('paid_on', ['label' => 'Paid On', 'type' => 'date'], date('Y-m-d')) ?>
            <?= form_field('method', ['label' => 'Method', 'type' => 'select', 'options' => ['bank' => 'Bank', 'cash' => 'Cash', 'cheque' => 'Cheque', 'online' => 'Online']], 'bank') ?>
        <?php else: ?><div class="col-12 small text-muted"><?= e(t('Paid on')) ?> <?= e(fmt_date($s['paid_on'])) ?> (<?= e($s['method']) ?>)</div><?php endif; ?>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><i class="bi bi-check2"></i> <?= e(t('Save')) ?></button></div>
</form>
