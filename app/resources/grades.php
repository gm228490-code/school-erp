<?php
return [
    'table' => 'grades', 'title' => 'Grading System', 'singular' => 'Grade', 'icon' => 'bi-award', 'group' => 'Examinations',
    'perm' => ['view' => 'exams.manage', 'manage' => 'exams.manage'],
    'intro' => 'Grades are assigned from the overall percentage. Passing criteria (overall pass % and per-subject passing) are configured in Settings → Grading.',
    'order' => 't.min_percent DESC', 'per_page' => 50,
    'columns' => [
        'name' => ['Grade', 'render' => fn($r) => '<span class="fw-bold">' . e($r['name']) . '</span>'],
        'min_percent' => ['Min %'], 'max_percent' => ['Max %'], 'grade_point' => ['Grade Point'], 'remarks' => ['Remarks'], 'is_pass' => ['Passing Grade', 'type' => 'bool'],
    ],
    'fields' => [
        'name' => ['label' => 'Grade', 'rules' => 'required|max:10', 'unique' => true],
        'grade_point' => ['label' => 'Grade Point', 'type' => 'decimal', 'rules' => 'required|numeric|min:0|max:5'],
        'min_percent' => ['label' => 'Minimum %', 'type' => 'decimal', 'rules' => 'required|numeric|min:0|max:100'],
        'max_percent' => ['label' => 'Maximum %', 'type' => 'decimal', 'rules' => 'required|numeric|min:0|max:100'],
        'remarks' => ['label' => 'Remarks', 'rules' => 'max:50'],
        'is_pass' => ['label' => 'Passing grade', 'type' => 'checkbox', 'default' => 1],
    ],
    'before_save' => function ($d, $id) {
        if ($d['max_percent'] < $d['min_percent']) {
            throw new ValidationException(['max_percent' => 'Maximum must be greater than minimum.']);
        }
        $overlap = DB::val('SELECT name FROM grades WHERE id<>? AND min_percent<=? AND max_percent>=?', [(int)$id, $d['max_percent'], $d['min_percent']]);
        if ($overlap) {
            throw new ValidationException(['min_percent' => "Range overlaps with grade $overlap."]);
        }
        return $d;
    },
];
