<?php
return [
    'table' => 'sections', 'title' => 'Sections', 'singular' => 'Section', 'icon' => 'bi-diagram-3', 'group' => 'Academics',
    'perm' => ['view' => 'academics.view', 'manage' => 'academics.manage'],
    'from' => 'sections t JOIN classes c ON c.id=t.class_id LEFT JOIN teachers tc ON tc.id=t.class_teacher_id',
    'select' => "t.*, c.name AS class_name, tc.name AS teacher_name, (SELECT COUNT(*) FROM students s WHERE s.section_id=t.id AND s.status='active') AS strength",
    'order' => 'c.numeric_order, t.name', 'search' => ['t.name', 'c.name', 'tc.name'],
    'scope' => fn() => Auth::scoped() ? ['t.id IN ' . DB::in($ids = Auth::teacherSectionIds()), $ids] : ['', []],
    'filters' => ['class' => ['label' => 'Class', 'sql' => 't.class_id', 'options' => fn() => class_options()]],
    'columns' => [
        'class_name' => ['Class', 'sort' => 'c.numeric_order'], 'name' => ['Section'], 'teacher_name' => ['Class Teacher', 'sort' => 'tc.name'], 'room' => ['Room'],
        'strength' => ['Strength', 'sort' => 'strength', 'render' => function ($r) {
            $pct = $r['capacity'] ? min(100, round($r['strength'] / $r['capacity'] * 100)) : 0;
            return '<div class="small">' . (int)$r['strength'] . ' / ' . (int)$r['capacity'] . '</div><div class="progress progress-thin" style="width:90px"><div class="progress-bar ' . ($pct >= 100 ? 'bg-danger' : '') . '" style="width:' . $pct . '%"></div></div>';
        }],
    ],
    'fields' => [
        'class_id' => ['label' => 'Class', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), 'rules' => 'required'],
        'name' => ['label' => 'Section Name', 'rules' => 'required|max:20', 'placeholder' => 'e.g. A / Blue / Iqbal'],
        'capacity' => ['label' => 'Capacity', 'type' => 'number', 'rules' => 'required|integer|min:1|max:500', 'default' => 40],
        'room' => ['label' => 'Room', 'rules' => 'max:30'],
        'class_teacher_id' => ['label' => 'Class Teacher', 'type' => 'select', 'options' => fn() => teacher_options(), 'col' => 12],
    ],
    'before_save' => function ($d, $id) {
        if (DB::val('SELECT COUNT(*) FROM sections WHERE class_id=? AND name=? AND id<>?', [$d['class_id'], $d['name'], (int)$id])) {
            throw new ValidationException(['name' => 'This section already exists in the class.']);
        }
        return $d;
    },
    'row_actions' => fn($r) => '<a class="btn btn-sm btn-outline-secondary" target="_blank" title="' . e(t('Class list')) . '" href="' . e(url('reports/class_list', ['class_id' => $r['class_id'], 'section_id' => $r['id']])) . '"><i class="bi bi-list-ol"></i></a> '
        . '<a class="btn btn-sm btn-outline-secondary" title="' . e(t('Timetable')) . '" href="' . e(url('timetable', ['section_id' => $r['id']])) . '"><i class="bi bi-calendar3-week"></i></a> ',
    'before_delete' => fn($id) => DB::val('SELECT COUNT(*) FROM students WHERE section_id=?', [$id]) ? 'Section has students and cannot be deleted.' : null,
];
