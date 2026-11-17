<?php
return [
    'table' => 'designations', 'title' => 'Designations', 'singular' => 'Designation', 'icon' => 'bi-briefcase', 'group' => 'Human Resource',
    'perm' => ['view' => 'academics.manage', 'manage' => 'academics.manage'],
    'from' => 'designations t LEFT JOIN departments d ON d.id=t.department_id', 'select' => 't.*, d.name AS dept_name',
    'order' => 't.name', 'search' => ['t.name', 'd.name'],
    'columns' => ['name' => ['Designation'], 'dept_name' => ['Department', 'sort' => 'd.name']],
    'fields' => [
        'name' => ['label' => 'Designation', 'rules' => 'required|max:100'],
        'department_id' => ['label' => 'Department', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM departments ORDER BY name')],
    ],
];
