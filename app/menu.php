<?php
/**
 * Sidebar navigation. Each item: [label, icon, route, params, permission(s), activeMatch]
 */
return [
    ['Main', [
        ['Dashboard', 'bi-grid-1x2', 'dashboard', [], 'dashboard.view'],
    ]],
    ['Students', [
        ['All Students', 'bi-people', 'students', [], 'students.view', 'students/index|students/show|students/edit'],
        ['New Admission', 'bi-person-plus', 'students/create', [], 'students.create'],
        ['Promotion', 'bi-arrow-up-circle', 'students/promote', [], 'students.promote'],
        ['ID Cards', 'bi-person-vcard', 'students/idcards', [], 'students.view'],
        ['Parents', 'bi-person-hearts', 'crud', ['res' => 'parents'], 'students.edit'],
    ]],
    ['Academics', [
        ['Sessions', 'bi-calendar-range', 'crud', ['res' => 'sessions'], 'academics.manage'],
        ['Classes', 'bi-building', 'crud', ['res' => 'classes'], 'academics.view'],
        ['Sections', 'bi-diagram-3', 'crud', ['res' => 'sections'], 'academics.view'],
        ['Subjects', 'bi-book', 'crud', ['res' => 'subjects'], 'academics.view'],
        ['Assign Subjects', 'bi-link-45deg', 'crud', ['res' => 'class_subjects'], 'academics.view'],
        ['Timetable', 'bi-calendar3-week', 'timetable', [], 'timetable.view'],
        ['Periods', 'bi-clock', 'crud', ['res' => 'periods'], 'timetable.manage'],
        ['Homework', 'bi-journal-text', 'homework', [], 'homework.view'],
    ]],
    ['Attendance', [
        ['Student Attendance', 'bi-check2-square', 'attendance/students', [], 'attendance.mark'],
        ['Employee Attendance', 'bi-person-check', 'attendance/employees', [], 'attendance.employee'],
        ['Attendance Reports', 'bi-clipboard-data', 'attendance/report', [], 'attendance.view'],
    ]],
    ['Examinations', [
        ['Exams', 'bi-pencil-square', 'crud', ['res' => 'exams'], 'exams.view'],
        ['Exam Schedule', 'bi-calendar-event', 'exams/schedule', [], 'exams.manage'],
        ['Marks Entry', 'bi-123', 'exams/marks', [], 'exams.marks'],
        ['Results', 'bi-trophy', 'exams/results', [], 'exams.view'],
        ['Grading', 'bi-award', 'crud', ['res' => 'grades'], 'exams.manage'],
    ]],
    ['Fees', [
        ['Collect Fee', 'bi-cash-coin', 'fees/collect', [], 'fees.collect'],
        ['Invoices', 'bi-receipt', 'fees', [], 'fees.view', 'fees/index|fees/invoice'],
        ['Payments', 'bi-wallet2', 'fees/payments', [], 'fees.view'],
        ['Generate Fees', 'bi-lightning-charge', 'fees/generate', [], 'fees.manage'],
        ['Fee Structure', 'bi-table', 'fees/structure', [], 'fees.manage'],
        ['Fee Categories', 'bi-tags', 'crud', ['res' => 'fee_categories'], 'fees.manage'],
        ['Discounts & Scholarships', 'bi-percent', 'crud', ['res' => 'discounts'], 'fees.manage'],
        ['Student Special Fees', 'bi-person-badge', 'crud', ['res' => 'student_fees'], 'fees.manage'],
    ]],
    ['Human Resource', [
        ['Teachers', 'bi-person-workspace', 'employees', ['type' => 'teacher'], 'teachers.view', 'type=teacher'],
        ['Staff', 'bi-person-gear', 'employees', ['type' => 'staff'], 'staff.view', 'type=staff'],
        ['Departments', 'bi-diagram-2', 'crud', ['res' => 'departments'], 'academics.manage'],
        ['Designations', 'bi-briefcase', 'crud', ['res' => 'designations'], 'academics.manage'],
        ['Leaves', 'bi-calendar-x', 'crud', ['res' => 'leaves'], 'leaves.view'],
        ['Performance', 'bi-star-half', 'crud', ['res' => 'performance'], 'leaves.view'],
    ]],
    ['Accounts', [
        ['Finance Overview', 'bi-graph-up-arrow', 'finance', [], 'finance.view'],
        ['Income', 'bi-box-arrow-in-down', 'crud', ['res' => 'incomes'], 'finance.view'],
        ['Expenses', 'bi-box-arrow-up', 'crud', ['res' => 'expenses'], 'finance.view'],
        ['Salaries', 'bi-cash-stack', 'finance/salaries', [], 'finance.salary'],
        ['Cash Book', 'bi-journal-bookmark', 'finance/cashbook', [], 'finance.view'],
        ['Income Categories', 'bi-bookmark-plus', 'crud', ['res' => 'income_categories'], 'finance.manage'],
        ['Expense Categories', 'bi-bookmark-dash', 'crud', ['res' => 'expense_categories'], 'finance.manage'],
    ]],
    ['Library', [
        ['Books', 'bi-book-half', 'crud', ['res' => 'books'], 'library.view'],
        ['Issue / Return', 'bi-arrow-left-right', 'library', [], 'library.view'],
        ['Book Categories', 'bi-collection', 'crud', ['res' => 'book_categories'], 'library.manage'],
    ]],
    ['Transport', [
        ['Vehicles', 'bi-bus-front', 'crud', ['res' => 'vehicles'], 'transport.view'],
        ['Routes', 'bi-signpost-split', 'crud', ['res' => 'routes'], 'transport.view'],
        ['Assign Students', 'bi-person-up', 'transport/assign', [], 'transport.manage'],
    ]],
    ['Communication', [
        ['Announcements', 'bi-megaphone', 'crud', ['res' => 'notices'], 'notices.view'],
        ['Fee Due Alerts', 'bi-bell', 'fees/reminders', [], 'notices.manage'],
        ['Message Log', 'bi-chat-left-text', 'crud', ['res' => 'message_logs'], 'notices.manage'],
    ]],
    ['Reports', [
        ['All Reports', 'bi-file-earmark-bar-graph', 'reports', [], 'reports.view'],
    ]],
    ['Administration', [
        ['Settings', 'bi-gear', 'settings', [], 'settings.manage'],
        ['Users', 'bi-person-lock', 'crud', ['res' => 'users'], 'users.manage'],
        ['Roles & Permissions', 'bi-shield-lock', 'roles', [], 'roles.manage'],
        ['Backup & Restore', 'bi-database-down', 'system/backup', [], 'system.backup'],
        ['Audit Logs', 'bi-activity', 'crud', ['res' => 'audit_logs'], 'system.audit'],
        ['Error Log', 'bi-bug', 'system/errors', [], 'system.audit'],
    ]],
];
