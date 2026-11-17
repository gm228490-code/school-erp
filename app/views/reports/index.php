<?php partial('page_head', ['title' => t('Reports'), 'icon' => 'bi-file-earmark-bar-graph', 'crumbs' => [t('Reports') => null]]);
$groups = [
    'Students' => [
        ['Student Admission Report', 'bi-person-plus', 'reports/admissions', [], 'reports.view'],
        ['Class-wise Student List', 'bi-list-ol', 'reports/class_list', [], 'students.view'],
        ['Class Strength Report', 'bi-bar-chart', 'reports/strength', [], 'reports.view'],
        ['Student Attendance (Monthly Register)', 'bi-calendar-check', 'attendance/report', ['mode' => 'monthly'], 'attendance.view'],
        ['Daily Attendance Summary', 'bi-calendar-day', 'attendance/report', ['mode' => 'daily'], 'attendance.view'],
        ['Examination Results', 'bi-trophy', 'exams/results', [], 'exams.view'],
    ],
    'Fees' => [
        ['Daily Fee Collection', 'bi-cash-coin', 'reports/collection', ['group' => 'day'], 'fees.view'],
        ['Monthly Fee Collection', 'bi-calendar2-month', 'reports/collection', ['group' => 'month', 'from' => date('Y-01-01')], 'fees.view'],
        ['Class-wise Fee Status', 'bi-building', 'reports/class_fees', [], 'fees.view'],
        ['Outstanding Fees', 'bi-hourglass-split', 'reports/outstanding', [], 'fees.view'],
        ['Fee Receipts (detailed)', 'bi-receipt', 'fees/payments', [], 'fees.view'],
    ],
    'Finance' => [
        ['Income Report', 'bi-box-arrow-in-down', 'reports/income', [], 'finance.view'],
        ['Expense Report', 'bi-box-arrow-up', 'reports/expenses', [], 'finance.view'],
        ['Income & Expense Statement', 'bi-graph-up-arrow', 'finance', [], 'finance.view'],
        ['Daily Cash Book', 'bi-journal-bookmark', 'finance/cashbook', [], 'finance.view'],
        ['Salary Report', 'bi-cash-stack', 'reports/salary', [], 'finance.salary'],
    ],
    'Staff & Others' => [
        ['Teacher Report', 'bi-person-workspace', 'reports/employees', ['type' => 'teacher'], 'teachers.view'],
        ['Staff Report', 'bi-person-gear', 'reports/employees', ['type' => 'staff'], 'staff.view'],
        ['Teacher / Staff Attendance', 'bi-person-check', 'attendance/report', ['mode' => 'employees'], 'attendance.view'],
        ['Library Report', 'bi-book', 'reports/library', [], 'library.view'],
        ['Transport Report', 'bi-bus-front', 'reports/transport', [], 'transport.view'],
    ],
];
?>
<div class="row g-4">
<?php foreach ($groups as $g => $items): $items = array_filter($items, fn($i) => can($i[4])); if (!$items) continue; ?>
    <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-header"><?= e(t($g)) ?></div>
        <div class="list-group list-group-flush">
            <?php foreach ($items as [$l, $ic, $r, $p]): ?><a class="list-group-item list-group-item-action d-flex align-items-center gap-2" href="<?= e(url($r, $p)) ?>"><i class="bi <?= e($ic) ?> text-primary"></i><?= e(t($l)) ?></a><?php endforeach; ?>
        </div></div></div>
<?php endforeach; ?>
</div>
<p class="small text-muted mt-3"><i class="bi bi-info-circle"></i> <?= e(t('Every report can be printed or saved as PDF from the browser print dialog, and exported to Excel or CSV.')) ?></p>
