<?php
return [
    'table' => 'periods', 'title' => 'Periods & Breaks', 'singular' => 'Period', 'icon' => 'bi-clock', 'group' => 'Timetable',
    'perm' => ['view' => 'timetable.manage', 'manage' => 'timetable.manage'],
    'order' => 't.sort_order, t.start_time', 'per_page' => 50,
    'columns' => [
        'sort_order' => ['#'], 'name' => ['Name'],
        'start_time' => ['Start', 'render' => fn($r) => e(fmt_time($r['start_time']))],
        'end_time' => ['End', 'render' => fn($r) => e(fmt_time($r['end_time']))],
        'is_break' => ['Break', 'type' => 'bool'],
    ],
    'fields' => [
        'name' => ['label' => 'Name', 'rules' => 'required|max:50'],
        'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => 'required|integer'],
        'start_time' => ['label' => 'Start Time', 'type' => 'time', 'rules' => 'required'],
        'end_time' => ['label' => 'End Time', 'type' => 'time', 'rules' => 'required'],
        'is_break' => ['label' => 'This is a break', 'type' => 'checkbox'],
    ],
    'before_save' => function ($d) {
        if ($d['end_time'] <= $d['start_time']) {
            throw new ValidationException(['end_time' => 'End time must be after start time.']);
        }
        return $d;
    },
];
