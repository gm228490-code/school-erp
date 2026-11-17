<?php
return [
    'table' => 'student_discounts', 'title' => 'Discounts & Scholarships', 'singular' => 'Discount', 'icon' => 'bi-percent', 'group' => 'Fees',
    'perm' => ['view' => 'fees.manage', 'manage' => 'fees.manage'],
    'intro' => 'Active discounts are applied automatically when fee invoices are generated. Leave "Fee Category" empty to apply to all monthly fees.',
    'from' => 'student_discounts t JOIN students s ON s.id=t.student_id JOIN classes c ON c.id=s.class_id LEFT JOIN fee_categories fc ON fc.id=t.fee_category_id',
    'select' => "t.*, CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) AS student_name, s.reg_no, c.name AS class_name, fc.name AS cat_name",
    'order' => 't.id DESC', 'search' => ['s.first_name', 's.last_name', 's.reg_no', 't.title'],
    'filters' => [
        'type' => ['label' => 'Type', 'sql' => 't.discount_type', 'options' => ['discount' => 'Discount', 'scholarship' => 'Scholarship']],
        'active' => ['label' => 'Active', 'sql' => 't.is_active', 'options' => [1 => 'Active', 0 => 'Inactive']],
    ],
    'columns' => [
        'student_name' => ['Student', 'sort' => 's.first_name', 'render' => fn($r) => '<a href="' . e(url('students/show', ['id' => $r['student_id']])) . '" class="fw-semibold">' . e($r['student_name']) . '</a><div class="small text-muted">' . e($r['reg_no'] . ' · ' . $r['class_name']) . '</div>'],
        'title' => ['Title'], 'discount_type' => ['Type', 'render' => fn($r) => '<span class="badge text-bg-' . ($r['discount_type'] === 'scholarship' ? 'info' : 'primary') . ' badge-soft">' . e(t(ucfirst($r['discount_type']))) . '</span>'],
        'value' => ['Value', 'render' => fn($r) => $r['value_type'] === 'percent' ? (float)$r['value'] . '%' : e(money($r['value']))],
        'cat_name' => ['Applies To', 'sort' => 'fc.name', 'render' => fn($r) => e($r['cat_name'] ?: t('All monthly fees'))],
        'is_active' => ['Active', 'type' => 'bool'],
    ],
    'fields' => [
        'student_id' => ['label' => 'Student', 'type' => 'select', 'options' => fn() => student_options(), 'rules' => 'required', 'col' => 12],
        'title' => ['label' => 'Title', 'rules' => 'required|max:100', 'placeholder' => 'e.g. Sibling discount / Merit scholarship'],
        'discount_type' => ['label' => 'Type', 'type' => 'select', 'options' => ['discount' => 'Discount', 'scholarship' => 'Scholarship'], 'rules' => 'required'],
        'value_type' => ['label' => 'Value Type', 'type' => 'select', 'options' => ['percent' => 'Percentage (%)', 'fixed' => 'Fixed Amount'], 'rules' => 'required'],
        'value' => ['label' => 'Value', 'type' => 'decimal', 'rules' => 'required|numeric|min:0'],
        'fee_category_id' => ['label' => 'Fee Category', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM fee_categories ORDER BY id'), 'placeholder' => 'All monthly fees'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 1],
        'remarks' => ['label' => 'Remarks', 'col' => 12, 'rules' => 'max:255'],
    ],
    'before_save' => function ($d) {
        if ($d['value_type'] === 'percent' && $d['value'] > 100) {
            throw new ValidationException(['value' => 'Percentage cannot exceed 100.']);
        }
        return $d;
    },
];
