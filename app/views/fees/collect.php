<?php partial('page_head', ['title' => t('Collect Fee'), 'icon' => 'bi-cash-coin', 'crumbs' => [t('Fees') => null, t('Collect') => null],
    'actions' => '<span class="badge text-bg-success badge-soft fs-6">' . e(t('Today')) . ': ' . e(money($today['total'])) . ' (' . (int)$today['n'] . ')</span>']); ?>
<div class="card mb-4"><div class="card-body">
    <label class="form-label"><?= e(t('Find student')) ?></label>
    <div><input type="text" class="form-control form-control-lg" data-student-search data-target="#csId" data-go="<?= e(url('fees/collect', ['student_id' => '__ID__'])) ?>"
        placeholder="<?= e(t('Type name, Reg No, father name, phone or B-Form, then press Enter…')) ?>" autocomplete="off"
        <?= $student ? 'value="' . e(student_name($student) . ' (' . $student['reg_no'] . ')') . '" data-chosen="1" onfocus="this.select()"' : 'autofocus' ?>></div>
    <div class="form-text"><?= e(t('Use ↑ ↓ to choose and Enter to open. Several words narrow the search, e.g. "Ali Khan" or "Ahmed Class 5".')) ?></div>
    <input type="hidden" id="csId">
</div></div>

<?php if ($student): ?>
<div class="row g-4">
    <div class="col-xl-8">
        <form method="post" action="<?= e(url('fees/pay')) ?>" class="card" id="payForm">
            <?= csrf_field() ?><input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><a class="fw-bold" href="<?= e(url('students/show', ['id' => $student['id']])) ?>"><?= e(student_name($student)) ?></a> <span class="text-muted small"><?= e($student['reg_no']) ?> · <?= e($student['class_name'] . ' ' . $student['section_name']) ?> · <?= e(t('Father')) ?>: <?= e($student['father_name']) ?></span></div>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('fees/ledger', ['student_id' => $student['id']])) ?>"><i class="bi bi-journal-text"></i> <?= e(t('Ledger')) ?></a>
                    <?php if (can('fees.manage')): ?><button type="button" class="btn btn-sm btn-light-soft" data-modal="<?= e(url('fees/create_form', ['student_id' => $student['id']])) ?>"><i class="bi bi-plus"></i> <?= e(t('Invoice')) ?></button><?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th><?= e(t('Invoice')) ?></th><th><?= e(t('Due')) ?></th><th class="text-end"><?= e(t('Balance')) ?></th><th style="width:130px"><?= e(t('Late Fine')) ?></th><th style="width:150px"><?= e(t('Pay Now')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($invoices as $i): $bal = FeeService::balance($i); ?>
                        <tr data-bal="<?= e($bal) ?>">
                            <td><a href="<?= e(url('fees/invoice', ['id' => $i['id']])) ?>" class="fw-semibold"><?= e($i['invoice_no']) ?></a><div class="small text-muted"><?= e($i['title']) ?></div></td>
                            <td class="small <?= $i['due_date'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>"><?= e(fmt_date($i['due_date'])) ?></td>
                            <td class="text-end fw-semibold"><?= e(money($bal)) ?></td>
                            <td><input type="number" step="1" min="0" name="fine[<?= (int)$i['id'] ?>]" class="form-control form-control-sm fine-in" value="<?= e((float)$i['late_fine']) ?>"></td>
                            <td><input type="number" step="1" min="0" name="pay[<?= (int)$i['id'] ?>]" class="form-control form-control-sm pay-in" value="<?= e($bal + (float)$i['late_fine']) ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$invoices): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-check2-circle text-success"></i><?= e(t('No pending dues. All invoices are paid.')) ?></div></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($invoices): ?>
            <div class="card-body border-top">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3"><label class="form-label"><?= e(t('Method')) ?></label><select name="method" class="form-select"><?= options(['cash' => t('Cash'), 'bank' => t('Bank'), 'cheque' => t('Cheque'), 'online' => t('Online / JazzCash / EasyPaisa')], 'cash') ?></select></div>
                    <div class="col-md-3"><label class="form-label"><?= e(t('Date')) ?></label><input type="date" name="paid_on" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-3"><label class="form-label"><?= e(t('Reference / Cheque No')) ?></label><input name="reference" class="form-control" maxlength="100"></div>
                    <div class="col-md-3"><label class="form-label"><?= e(t('Remarks')) ?></label><input name="remarks" class="form-control" maxlength="255"></div>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                    <div class="fs-5"><?= e(t('Total')) ?>: <b id="payTotal">0</b></div>
                    <button class="btn btn-success btn-lg"><i class="bi bi-check2-circle"></i> <?= e(t('Receive Payment & Print Receipt')) ?></button>
                </div>
                <div class="form-text"><?= e(t('Partial payments are allowed. Late fine is added to the invoice and included in the amount paid.')) ?></div>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-header"><?= e(t('Recent Receipts')) ?></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recent as $r): ?><li class="list-group-item d-flex justify-content-between"><a target="_blank" href="<?= e(url('fees/receipt', ['id' => $r['id']])) ?>"><?= e($r['receipt_no']) ?></a><span class="small text-muted"><?= e(fmt_date($r['paid_on'])) ?></span><span class="fw-semibold"><?= e(money($r['amount'])) ?></span></li><?php endforeach; ?>
                <?php if (!$recent): ?><li class="list-group-item small text-muted"><?= e(t('No payments yet.')) ?></li><?php endif; ?>
            </ul></div>
    </div>
</div>
<?php endif; ?>
<?php ob_start(); ?>
<script>
(function () {
  const f = document.getElementById('payForm'); if (!f) return;
  const fmt = n => <?= json_encode(setting('currency_symbol', 'Rs.')) ?> + ' ' + Number(n).toLocaleString();
  const calc = () => { let t = 0; f.querySelectorAll('.pay-in').forEach(i => t += Number(i.value || 0)); document.getElementById('payTotal').textContent = fmt(t); };
  f.querySelectorAll('tr[data-bal]').forEach(tr => {
    const fine = tr.querySelector('.fine-in'), pay = tr.querySelector('.pay-in'), bal = Number(tr.dataset.bal);
    fine.addEventListener('input', () => { pay.value = bal + Number(fine.value || 0); calc(); });
    pay.addEventListener('input', calc);
  });
  calc();
})();
</script>
<?php $scripts = ob_get_clean(); ?>
