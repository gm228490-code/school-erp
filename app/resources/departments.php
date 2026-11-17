<?php
return [
    'table' => 'departments', 'title' => 'Departments', 'singular' => 'Department', 'icon' => 'bi-diagram-2', 'group' => 'Human Resource',
    'perm' => ['view' => 'academics.manage', 'manage' => 'academics.manage'],
    'order' => 't.name', 'search' => ['t.name'],
    'columns' => [
        'name' => ['Department'], 'description' => ['Description'],
        'emp' => ['Employees', 'sort' => false, 'render' => fn($r) => (int)DB::val('SELECT (SELECT COUNT(*) FROM teachers WHERE department_id=?)+(SELECT COUNT(*) FROM staff WHERE department_id=?)', [$r['id'], $r['id']])],
    ],
    'fields' => [
        'name' => ['label' => 'Department Name', 'rules' => 'required|max:100', 'unique' => true],
        'description' => ['label' => 'Description', 'rules' => 'max:255'],
    ],
];
