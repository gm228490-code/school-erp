<?php
return [
    'table' => 'class_subjects', 'title' => 'Assign Subjects & Teachers', 'singular' => 'Subject Assignment', 'icon' => 'bi-link-45deg', 'group' => 'Academics',
    'perm' => ['view' => 'academics.view', 'manage' => 'academics.manage'],
    'intro' => 'Assign subjects to each class section and the teacher who teaches it. Teachers can only access the sections and subjects assigned to them.',
    'from' => 'class_subjects t JOIN classes c ON c.id=t.class_id JOIN sections s ON s.id=t.section_id JOIN subjects sb ON sb.id=t.subject_id LEFT JOIN teachers tc ON tc.id=t.teacher_id',
    'select' => 't.*, c.name AS class_name, s.name AS section_name, sb.name AS subject_name, tc.name AS teacher_name',
    'order' => 'c.numeric_order, s.name, sb.name', 'search' => ['sb.name', 'tc.name', 'c.name'], 'per_page' => 50,
    'scope' => fn() => Auth::scoped() ? ['t.section_id IN ' . DB::in($ids = Auth::teacherSectionIds()), $ids] : ['', []],
    'filters' => [
        'class' => ['label' => 'Class', 'sql' => 't.class_id', 'options' => fn() => class_options()],
        'teacher' => ['label' => 'Teacher', 'sql' => 't.teacher_id', 'options' => fn() => teacher_options()],
    ],
    'columns' => ['class_name' => ['Class', 'sort' => 'c.numeric_order'], 'section_name' => ['Section', 'sort' => 's.name'], 'subject_name' => ['Subject', 'sort' => 'sb.name'], 'teacher_name' => ['Teacher', 'sort' => 'tc.name']],
    'fields' => [
        'class_id' => ['label' => 'Class', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), 'rules' => 'required', 'attrs' => 'id="cs_class"'],
        'section_id' => ['label' => 'Section', 'type' => 'select', 'options' => fn() => section_options(), 'rules' => 'required', 'attrs' => 'data-sections-for="#cs_class"'],
        'subject_id' => ['label' => 'Subject', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM subjects ORDER BY name'), 'rules' => 'required'],
        'teacher_id' => ['label' => 'Teacher', 'type' => 'select', 'options' => fn() => teacher_options()],
        'all_sections' => ['label' => 'Apply to all sections of this class', 'type' => 'checkbox', 'create_only' => true],
    ],
    'before_save' => function ($d, $id) {
        if ((int)DB::val('SELECT class_id FROM sections WHERE id=?', [$d['section_id']]) !== (int)$d['class_id']) {
            throw new ValidationException(['section_id' => 'Section does not belong to the selected class.']);
        }
        $all = !empty($d['all_sections']) && !$id;
        unset($d['all_sections']);
        if (DB::val('SELECT COUNT(*) FROM class_subjects WHERE section_id=? AND subject_id=? AND id<>?', [$d['section_id'], $d['subject_id'], (int)$id])) {
            throw new ValidationException(['subject_id' => 'This subject is already assigned to the section.']);
        }
        if ($all) {
            foreach (DB::col('SELECT id FROM sections WHERE class_id=? AND id<>?', [$d['class_id'], $d['section_id']]) as $sec) {
                DB::upsert('class_subjects', ['class_id' => $d['class_id'], 'section_id' => $sec, 'subject_id' => $d['subject_id'], 'teacher_id' => $d['teacher_id']], ['teacher_id']);
            }
        }
        return $d;
    },
];
