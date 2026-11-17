<?php
$freq = ['one_time' => 'One Time', 'monthly' => 'Monthly', 'annual' => 'Annual', 'per_exam' => 'Per Exam'];
return [
    'table' => 'fee_categories', 'title' => 'Fee Categories', 'singular' => 'Fee Category', 'icon' => 'bi-tags', 'group' => 'Fees',
    'perm' => ['view' => 'fees.manage', 'manage' => 'fees.manage'],
    'intro' => 'Monthly categories are included automatically when monthly fees are generated. Annual / one-time / exam fees can be added to any month during generation. Transport Fee is taken from the student\'s assigned route.',
    'order' => 't.id', 'search' => ['t.name'],
    'columns' => [
        'name' => ['Category'], 'frequency' => ['Frequency', 'render' => fn($r) => e(t($freq[$r['frequency']] ?? $r['frequency']))],
        'description' => ['Description'], 'is_system' => ['Built-in', 'type' => 'bool'],
    ],
    'fields' => [
        'name' => ['label' => 'Category Name', 'rules' => 'required|max:100', 'unique' => true],
        'frequency' => ['label' => 'Frequency', 'type' => 'select', 'options' => $freq, 'rules' => 'required', 'default' => 'monthly'],
        'description' => ['label' => 'Description', 'col' => 12, 'rules' => 'max:255'],
    ],
    'deletable' => fn($r) => !$r['is_system'],
    'before_delete' => fn($id, $r) => $r['is_system'] ? 'Built-in categories cannot be deleted.' : null,
];
