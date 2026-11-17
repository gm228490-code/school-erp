<?php
return [
    'table' => 'student_fees', 'title' => 'Individual Student Fees', 'singular' => 'Student Fee', 'icon' => 'bi-person-badge', 'group' => 'Fees',
    'perm' => ['view' => 'fees.manage', 'manage' => 'fees.manage'],
    'intro' => 'Overrides the class fee structure for a specific student (e.g. a special tuition rate). Set amount to 0 to exempt the student from that fee.',
    'from' => 'student_fees t JOIN students s ON s.id=t.student_id JOIN classes c ON c.id=s.class_id JOIN fee_categories fc ON fc.id=t.fee_category_id',
    'select' => "t.*, CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) AS student_name, s.reg_no, c.name AS class_name, fc.name AS cat_name,
                 (SELECT fs.amount FROM fee_structures fs WHERE fs.class_id=s.class_id AND fs.fee_category_id=t.fee_category_id AND fs.session_id=" . session_id_current() . ") AS class_amount",
    'order' => 't.id DESC', 'search' => ['s.first_name', 's.last_name', 's.reg_no', 'fc.name'],
    'columns' => [
        'student_name' => ['Student', 'sort' => 's.first_name', 'render' => fn($r) => '<span class="fw-semibold">' . e($r['student_name']) . '</span><div class="small text-muted">' . e($r['reg_no'] . ' · ' . $r['class_name']) . '</div>'],
        'cat_name' => ['Fee Category', 'sort' => 'fc.name'], 'class_amount' => ['Class Rate', 'type' => 'money'], 'amount' => ['Student Rate', 'type' => 'money'], 'remarks' => ['Remarks'],
    ],
    'fields' => [
        'student_id' => ['label' => 'Student', 'type' => 'select', 'options' => fn() => student_options(), 'rules' => 'required', 'col' => 12],
        'fee_category_id' => ['label' => 'Fee Category', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM fee_categories ORDER BY id'), 'rules' => 'required'],
        'amount' => ['label' => 'Amount', 'type' => 'decimal', 'rules' => 'required|numeric|min:0'],
        'remarks' => ['label' => 'Remarks', 'col' => 12, 'rules' => 'max:255'],
    ],
    'before_save' => function ($d, $id) {
        if (DB::val('SELECT COUNT(*) FROM student_fees WHERE student_id=? AND fee_category_id=? AND id<>?', [$d['student_id'], $d['fee_category_id'], (int)$id])) {
            throw new ValidationException(['fee_category_id' => 'This student already has a special rate for this category.']);
        }
        return $d;
    },
];
