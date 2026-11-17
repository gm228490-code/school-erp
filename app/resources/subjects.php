<?php
return [
    'table' => 'subjects', 'title' => 'Subjects', 'singular' => 'Subject', 'icon' => 'bi-book', 'group' => 'Academics',
    'perm' => ['view' => 'academics.view', 'manage' => 'academics.manage'],
    'order' => 't.name', 'search' => ['t.name', 't.code'],
    'filters' => ['type' => ['label' => 'Type', 'sql' => 't.type', 'options' => ['theory' => 'Theory', 'practical' => 'Practical', 'both' => 'Theory + Practical']]],
    'columns' => [
        'name' => ['Subject'], 'code' => ['Code'], 'type' => ['Type', 'render' => fn($r) => e(t(ucfirst($r['type'])))],
        'classes' => ['Taught In', 'sort' => false, 'render' => fn($r) => e(implode(', ', DB::col('SELECT DISTINCT c.name FROM class_subjects cs JOIN classes c ON c.id=cs.class_id WHERE cs.subject_id=? ORDER BY c.numeric_order', [$r['id']])))],
    ],
    'fields' => [
        'name' => ['label' => 'Subject Name', 'rules' => 'required|max:100', 'unique' => true],
        'code' => ['label' => 'Subject Code', 'rules' => 'max:20', 'unique' => true],
        'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['theory' => 'Theory', 'practical' => 'Practical', 'both' => 'Theory + Practical'], 'rules' => 'required', 'default' => 'theory'],
        'description' => ['label' => 'Description', 'rules' => 'max:255'],
    ],
];
