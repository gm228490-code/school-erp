<?php
return [
    'table' => 'academic_sessions', 'title' => 'Academic Sessions', 'singular' => 'Session', 'icon' => 'bi-calendar-range', 'group' => 'Academics',
    'perm' => ['view' => 'academics.manage', 'manage' => 'academics.manage'],
    'order' => 't.start_date DESC', 'search' => ['t.name'],
    'columns' => [
        'name' => ['Session'], 'start_date' => ['Start Date', 'type' => 'date'], 'end_date' => ['End Date', 'type' => 'date'],
        'is_current' => ['Current', 'type' => 'bool'], 'status' => ['Status', 'type' => 'badge'],
        'students' => ['Students', 'sort' => false, 'render' => fn($r) => (int)DB::val('SELECT COUNT(*) FROM students WHERE session_id=?', [$r['id']])],
    ],
    'fields' => [
        'name' => ['label' => 'Session Name', 'rules' => 'required|max:30', 'unique' => true, 'placeholder' => 'e.g. 2026-2027'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'closed' => 'Closed'], 'rules' => 'required', 'default' => 'active'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date', 'rules' => 'required'],
        'end_date' => ['label' => 'End Date', 'type' => 'date', 'rules' => 'required'],
        'is_current' => ['label' => 'Set as current session', 'type' => 'checkbox'],
    ],
    'before_save' => function ($d) {
        if ($d['end_date'] <= $d['start_date']) throw new ValidationException(['end_date' => 'End date must be after start date.']);
        return $d;
    },
    'after_save' => function ($id, $d) {
        if (!empty($d['is_current'])) DB::q('UPDATE academic_sessions SET is_current=0 WHERE id<>?', [$id]);
        if (!DB::val('SELECT COUNT(*) FROM academic_sessions WHERE is_current=1')) DB::q('UPDATE academic_sessions SET is_current=1 WHERE id=?', [$id]);
    },
    'before_delete' => fn($id, $r) => $r['is_current'] ? 'The current session cannot be deleted.' : null,
    'row_actions' => fn($r) => $r['status'] === 'active' && $r['is_current'] && can('academics.manage')
        ? '<a class="btn btn-sm btn-outline-warning" href="' . e(url('students/promote')) . '" title="' . e(t('Close year & promote')) . '"><i class="bi bi-door-closed"></i></a> ' : '',
];
