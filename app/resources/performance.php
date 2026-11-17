<?php
return [
    'table' => 'teacher_performance', 'title' => 'Teacher Performance', 'singular' => 'Performance Review', 'icon' => 'bi-star-half', 'group' => 'Human Resource',
    'perm' => ['view' => 'leaves.view', 'manage' => 'leaves.manage'], 'stamp' => ['reviewed_by'],
    'from' => 'teacher_performance t JOIN teachers tc ON tc.id=t.teacher_id',
    'select' => 't.*, tc.name AS teacher_name, tc.employee_no',
    'order' => 't.review_date DESC', 'search' => ['tc.name', 't.category', 't.remarks'],
    'filters' => ['teacher' => ['label' => 'Teacher', 'sql' => 't.teacher_id', 'options' => fn() => teacher_options()]],
    'columns' => [
        'teacher_name' => ['Teacher', 'sort' => 'tc.name'], 'review_date' => ['Date', 'type' => 'date'], 'period' => ['Period'], 'category' => ['Category'],
        'rating' => ['Rating', 'render' => fn($r) => str_repeat('<i class="bi bi-star-fill text-warning"></i>', (int)$r['rating']) . str_repeat('<i class="bi bi-star text-muted"></i>', 5 - (int)$r['rating'])],
        'remarks' => ['Remarks'],
    ],
    'fields' => [
        'teacher_id' => ['label' => 'Teacher', 'type' => 'select', 'options' => fn() => teacher_options(), 'rules' => 'required'],
        'review_date' => ['label' => 'Review Date', 'type' => 'date', 'rules' => 'required', 'default' => date('Y-m-d')],
        'period' => ['label' => 'Period', 'rules' => 'max:50', 'placeholder' => 'e.g. First Term 2026'],
        'category' => ['label' => 'Category', 'type' => 'select', 'options' => ['Overall' => 'Overall', 'Teaching Quality' => 'Teaching Quality', 'Punctuality' => 'Punctuality', 'Classroom Management' => 'Classroom Management', 'Student Results' => 'Student Results', 'Conduct' => 'Conduct'], 'rules' => 'required', 'default' => 'Overall'],
        'rating' => ['label' => 'Rating (1-5)', 'type' => 'select', 'options' => [5 => '5 - Excellent', 4 => '4 - Very Good', 3 => '3 - Good', 2 => '2 - Fair', 1 => '1 - Poor'], 'rules' => 'required'],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 12],
    ],
];
