<?php
/**
 * Permission catalog (module => [slug => label]) and default role grants.
 * Super Admin bypasses all checks. Parents & students use the portal only.
 */
return [
    'catalog' => [
        'Dashboard'   => ['dashboard.view' => 'View dashboard'],
        'Students'    => ['students.view' => 'View students', 'students.create' => 'Admit students', 'students.edit' => 'Edit students & documents',
                          'students.delete' => 'Delete students', 'students.promote' => 'Promote / transfer / withdraw', 'students.import' => 'Import / export students'],
        'Teachers'    => ['teachers.view' => 'View teachers', 'teachers.create' => 'Add teachers', 'teachers.edit' => 'Edit teachers', 'teachers.delete' => 'Delete teachers'],
        'Staff'       => ['staff.view' => 'View staff', 'staff.create' => 'Add staff', 'staff.edit' => 'Edit staff', 'staff.delete' => 'Delete staff'],
        'Academics'   => ['academics.view' => 'View classes, sections & subjects', 'academics.manage' => 'Manage classes, sections, subjects & sessions'],
        'Attendance'  => ['attendance.view' => 'View attendance reports', 'attendance.mark' => 'Mark student attendance', 'attendance.employee' => 'Mark teacher/staff attendance'],
        'HR'          => ['leaves.view' => 'View leaves & performance', 'leaves.manage' => 'Manage / approve leaves & performance'],
        'Fees'        => ['fees.view' => 'View fees & invoices', 'fees.collect' => 'Collect fees', 'fees.manage' => 'Manage fee structure, discounts & invoices', 'fees.delete' => 'Cancel invoices / payments'],
        'Examinations'=> ['exams.view' => 'View exams & results', 'exams.manage' => 'Manage exams, schedules & grades', 'exams.marks' => 'Enter marks', 'exams.results' => 'Generate & publish results'],
        'Timetable'   => ['timetable.view' => 'View timetables', 'timetable.manage' => 'Manage timetables & periods'],
        'Finance'     => ['finance.view' => 'View accounts & reports', 'finance.manage' => 'Manage income & expenses', 'finance.salary' => 'Process salaries'],
        'Library'     => ['library.view' => 'View library', 'library.manage' => 'Manage books', 'library.issue' => 'Issue / return books'],
        'Transport'   => ['transport.view' => 'View transport', 'transport.manage' => 'Manage vehicles, routes & assignments'],
        'Homework'    => ['homework.view' => 'View homework', 'homework.manage' => 'Create homework & review submissions'],
        'Notices'     => ['notices.view' => 'View announcements', 'notices.manage' => 'Publish announcements & alerts'],
        'Reports'     => ['reports.view' => 'View & export reports'],
        'Settings'    => ['settings.manage' => 'Manage school settings'],
        'Users'       => ['users.manage' => 'Manage user accounts', 'roles.manage' => 'Manage roles & permissions'],
        'System'      => ['system.backup' => 'Backup & restore database', 'system.audit' => 'View audit & error logs'],
    ],
    'roles' => [
        ['super_admin', 'Super Admin', 'Full unrestricted access', '*'],
        ['admin', 'School Admin', 'Manages the whole school', '*-roles.manage'],
        ['principal', 'Principal', 'Academic head', ['dashboard.view', 'students.view', 'students.edit', 'students.promote', 'teachers.view', 'staff.view',
            'academics.view', 'academics.manage', 'attendance.view', 'attendance.mark', 'attendance.employee', 'leaves.view', 'leaves.manage',
            'fees.view', 'exams.view', 'exams.manage', 'exams.marks', 'exams.results', 'timetable.view', 'timetable.manage', 'finance.view',
            'library.view', 'transport.view', 'homework.view', 'homework.manage', 'notices.view', 'notices.manage', 'reports.view']],
        ['accountant', 'Accountant', 'Fees & finance', ['dashboard.view', 'students.view', 'teachers.view', 'staff.view', 'fees.view', 'fees.collect',
            'fees.manage', 'fees.delete', 'finance.view', 'finance.manage', 'finance.salary', 'notices.view', 'reports.view']],
        ['teacher', 'Teacher', 'Assigned classes & subjects only', ['dashboard.view', 'students.view', 'attendance.view', 'attendance.mark', 'exams.view',
            'exams.marks', 'timetable.view', 'homework.view', 'homework.manage', 'notices.view', 'academics.view']],
        ['receptionist', 'Receptionist', 'Front desk & admissions', ['dashboard.view', 'students.view', 'students.create', 'students.edit', 'teachers.view',
            'staff.view', 'fees.view', 'fees.collect', 'attendance.view', 'notices.view', 'academics.view', 'transport.view']],
        ['librarian', 'Librarian', 'Library management', ['dashboard.view', 'students.view', 'library.view', 'library.manage', 'library.issue', 'notices.view', 'reports.view']],
        ['transport_manager', 'Transport Manager', 'Vehicles & routes', ['dashboard.view', 'students.view', 'transport.view', 'transport.manage', 'notices.view', 'reports.view']],
        ['parent', 'Parent', 'Parent portal', []],
        ['student', 'Student', 'Student portal', []],
    ],
];
