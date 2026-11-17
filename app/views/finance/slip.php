<div class="kv-print">
    <b><?= e(t('Slip No')) ?>:</b><span><?= e($s['slip_no']) ?></span><b><?= e(t('Month')) ?>:</b><span><?= e(fmt_month($s['salary_month'])) ?></span>
    <b><?= e(t('Name')) ?>:</b><span><?= e($e['name'] ?? '') ?></span><b><?= e(t('Employee No')) ?>:</b><span><?= e($e['employee_no'] ?? '') ?></span>
    <b><?= e(t('Designation')) ?>:</b><span><?= e($e['desig'] ?? '') ?></span><b><?= e(t('Department')) ?>:</b><span><?= e($e['dept'] ?? '') ?></span>
    <b><?= e(t('CNIC')) ?>:</b><span><?= e($e['cnic'] ?? '') ?></span><b><?= e(t('Bank / Account')) ?>:</b><span><?= e(trim(($e['bank_name'] ?? '') . ' ' . ($e['bank_account'] ?? ''))) ?></span>
</div>
<div class="row">
    <div class="col-6"><table class="table table-bordered table-sm"><tr><th colspan="2"><?= e(t('Earnings')) ?></th></tr>
        <tr><td><?= e(t('Basic Salary')) ?></td><td class="text-end"><?= e(money($s['basic'])) ?></td></tr>
        <tr><td><?= e(t('Allowances')) ?></td><td class="text-end"><?= e(money($s['allowances'])) ?></td></tr>
        <tr><td><?= e(t('Bonus')) ?></td><td class="text-end"><?= e(money($s['bonus'])) ?></td></tr>
        <tr><th><?= e(t('Gross')) ?></th><th class="text-end"><?= e(money($s['basic'] + $s['allowances'] + $s['bonus'])) ?></th></tr></table></div>
    <div class="col-6"><table class="table table-bordered table-sm"><tr><th colspan="2"><?= e(t('Deductions')) ?></th></tr>
        <tr><td><?= e(t('Deductions')) ?> <?= $s['remarks'] ? '<small>(' . e($s['remarks']) . ')</small>' : '' ?></td><td class="text-end"><?= e(money($s['deductions'])) ?></td></tr>
        <tr><td><?= e(t('Attendance')) ?></td><td class="text-end small">P <?= (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0) ?> · A <?= (int)($att['absent'] ?? 0) ?> · L <?= (int)($att['leave'] ?? 0) ?></td></tr>
        <tr><th><?= e(t('Total Deductions')) ?></th><th class="text-end"><?= e(money($s['deductions'])) ?></th></tr></table></div>
</div>
<div class="text-center my-3"><span class="amount-box"><?= e(t('Net Salary')) ?>: <?= e(money($s['net_salary'])) ?></span></div>
<p class="text-center small"><?= e(t('Status')) ?>: <b><?= e(strtoupper($s['status'])) ?></b> <?= $s['paid_on'] ? '· ' . e(t('Paid on')) . ' ' . e(fmt_date($s['paid_on'])) . ' (' . e($s['method']) . ')' : '' ?></p>
<div class="sig-row"><div><?= e(t('Employee')) ?></div><div><?= e(t('Accountant')) ?></div><div><?= e(t('Principal')) ?></div></div>
