<?php
return [
    'table' => 'classes', 'title' => 'Classes', 'singular' => 'Class', 'icon' => 'bi-building', 'group' => 'Academics',
    'perm' => ['view' => 'academics.view', 'manage' => 'academics.manage'],
    'order' => 't.numeric_order, t.name', 'search' => ['t.name'],
    'scope' => fn() => Auth::scoped() ? ['t.id IN (SELECT class_id FROM sections WHERE id IN ' . DB::in($ids = Auth::teacherSectionIds()) . ')', $ids] : ['', []],
    'columns' => [
        'name' => ['Class'], 'numeric_order' => ['Order'],
        'sections' => ['Sections', 'sort' => false, 'render' => fn($r) => e(implode(', ', DB::col('SELECT name FROM sections WHERE class_id=? ORDER BY name', [$r['id']]))) ?: '<span class="text-muted">—</span>'],
        'students' => ['Students', 'sort' => false, 'render' => fn($r) => '<a href="' . e(url('students', ['class_id' => $r['id']])) . '">' . (int)DB::val("SELECT COUNT(*) FROM students WHERE class_id=? AND status='active'", [$r['id']]) . '</a>'],
        'subjects' => ['Subjects', 'sort' => false, 'render' => fn($r) => (int)DB::val('SELECT COUNT(DISTINCT subject_id) FROM class_subjects WHERE class_id=?', [$r['id']])],
        'description' => ['Description'],
    ],
    'fields' => [
        'name' => ['label' => 'Class Name', 'rules' => 'required|max:50', 'unique' => true, 'placeholder' => 'e.g. Class 5 / Grade 5 / Nursery'],
        'numeric_order' => ['label' => 'Order (used for promotion)', 'type' => 'number', 'rules' => 'required|integer', 'default' => 0, 'help' => 'Students are promoted to the class with the next higher order.'],
        'description' => ['label' => 'Description', 'col' => 12, 'rules' => 'max:255'],
    ],
    'row_actions' => fn($r) => '<a class="btn btn-sm btn-outline-secondary" target="_blank" title="' . e(t('Class list')) . '" href="' . e(url('reports/class_list', ['class_id' => $r['id']])) . '"><i class="bi bi-list-ol"></i></a> ',
    'before_delete' => fn($id) => DB::val('SELECT COUNT(*) FROM students WHERE class_id=?', [$id]) ? 'Class has students and cannot be deleted.' : (DB::val('SELECT COUNT(*) FROM sections WHERE class_id=?', [$id]) ? 'Delete the sections of this class first.' : null),
];
