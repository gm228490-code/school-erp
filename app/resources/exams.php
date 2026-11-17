<?php
$types = ['monthly_test' => 'Monthly Test', 'midterm' => 'Midterm', 'final' => 'Final', 'other' => 'Other'];
return [
    'table' => 'exams', 'title' => 'Examinations', 'singular' => 'Exam', 'icon' => 'bi-pencil-square', 'group' => 'Examinations',
    'perm' => ['view' => 'exams.view', 'manage' => 'exams.manage'],
    'select' => 't.*, (SELECT COUNT(DISTINCT class_id) FROM exam_subjects es WHERE es.exam_id=t.id) AS class_count, (SELECT COUNT(*) FROM results r WHERE r.exam_id=t.id) AS result_count',
    'order' => 't.start_date DESC, t.id DESC', 'search' => ['t.name'],
    'scope' => fn() => ['t.session_id=?', [session_id_current()]],
    'filters' => ['type' => ['label' => 'Type', 'sql' => 't.exam_type', 'options' => $types]],
    'columns' => [
        'name' => ['Exam', 'render' => fn($r) => '<span class="fw-semibold">' . e($r['name']) . '</span>'],
        'exam_type' => ['Type', 'render' => fn($r) => e(t($types[$r['exam_type']] ?? $r['exam_type']))],
        'start_date' => ['Start', 'type' => 'date'], 'end_date' => ['End', 'type' => 'date'],
        'class_count' => ['Classes', 'sort' => 'class_count'], 'result_count' => ['Results', 'sort' => 'result_count'],
        'is_published' => ['Published', 'type' => 'bool'],
    ],
    'fields' => [
        'name' => ['label' => 'Exam Name', 'rules' => 'required|max:100', 'placeholder' => 'e.g. First Term Examination 2026'],
        'exam_type' => ['label' => 'Exam Type', 'type' => 'select', 'options' => $types, 'rules' => 'required', 'default' => 'midterm'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date'],
        'end_date' => ['label' => 'End Date', 'type' => 'date'],
        'description' => ['label' => 'Description / Instructions', 'col' => 12, 'rules' => 'max:255'],
    ],
    'before_save' => function ($d, $id) {
        if ($d['start_date'] && $d['end_date'] && $d['end_date'] < $d['start_date']) {
            throw new ValidationException(['end_date' => 'End date must be on or after start date.']);
        }
        if (!$id) {
            $d['session_id'] = session_id_current();
        }
        if (DB::val('SELECT COUNT(*) FROM exams WHERE session_id=? AND name=? AND id<>?', [session_id_current(), $d['name'], (int)$id])) {
            throw new ValidationException(['name' => 'An exam with this name already exists in this session.']);
        }
        return $d;
    },
    'row_actions' => fn($r) => (can('exams.manage') ? '<a class="btn btn-sm btn-outline-secondary" title="' . e(t('Schedule')) . '" href="' . e(url('exams/schedule', ['exam_id' => $r['id']])) . '"><i class="bi bi-calendar-event"></i></a> ' : '')
        . (can('exams.marks') ? '<a class="btn btn-sm btn-outline-secondary" title="' . e(t('Marks Entry')) . '" href="' . e(url('exams/marks', ['exam_id' => $r['id']])) . '"><i class="bi bi-123"></i></a> ' : '')
        . '<a class="btn btn-sm btn-outline-primary" title="' . e(t('Results')) . '" href="' . e(url('exams/results', ['exam_id' => $r['id']])) . '"><i class="bi bi-trophy"></i></a> ',
    'before_delete' => fn($id, $r) => $r['result_count'] ? 'Results exist for this exam. Delete is not allowed.' : null,
];
