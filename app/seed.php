<?php
declare(strict_types=1);

/**
 * Base seed data. Idempotent: safe to run multiple times.
 */
function seed_permissions(): void
{
    $cfg = require APP_PATH . '/permissions.php';
    $all = [];
    foreach ($cfg['catalog'] as $module => $perms) {
        foreach ($perms as $slug => $name) {
            DB::q('INSERT INTO permissions (slug, module, name) VALUES (?,?,?) ON DUPLICATE KEY UPDATE module=VALUES(module), name=VALUES(name)', [$slug, $module, $name]);
            $all[] = $slug;
        }
    }
    $permIds = DB::pairs('SELECT slug, id FROM permissions');
    foreach ($cfg['roles'] as [$slug, $name, $desc, $grants]) {
        $exists = DB::val('SELECT id FROM roles WHERE slug=?', [$slug]);
        if ($exists) {
            continue; // never overwrite customised role permissions
        }
        $rid = DB::insert('roles', ['name' => $name, 'slug' => $slug, 'description' => $desc, 'is_system' => 1]);
        if ($grants === '*') {
            $list = $all;
        } elseif (is_string($grants) && str_starts_with($grants, '*-')) {
            $list = array_diff($all, explode(',', substr($grants, 2)));
        } else {
            $list = $grants;
        }
        foreach ($list as $p) {
            if (isset($permIds[$p])) {
                DB::q('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)', [$rid, $permIds[$p]]);
            }
        }
    }
}

function seed_base(array $school): void
{
    seed_permissions();

    $defaults = [
        'school_name' => $school['name'] ?? 'My School', 'school_short_name' => $school['short'] ?? '', 'school_address' => $school['address'] ?? '',
        'school_phone' => $school['phone'] ?? '', 'school_email' => $school['email'] ?? '', 'school_website' => '', 'school_logo' => '',
        'principal_name' => '', 'school_motto' => '', 'school_reg_no' => '',
        'school_start_time' => '08:00', 'school_end_time' => '14:00', 'working_days' => '1,2,3,4,5,6',
        'currency_code' => 'PKR', 'currency_symbol' => 'Rs.', 'currency_decimals' => '0', 'date_format' => 'd-M-Y', 'default_lang' => 'en',
        'reg_prefix' => 'STD-', 'invoice_prefix' => 'INV-', 'receipt_prefix' => 'RCP-', 'teacher_prefix' => 'TCH-', 'staff_prefix' => 'STF-',
        'fee_due_day' => '10', 'late_fine_type' => 'fixed', 'late_fine_amount' => '200', 'late_fine_max' => '1000',
        'pass_percentage' => '33', 'pass_each_subject' => '1', 'result_remarks' => '1',
        'receipt_note' => 'Fee once paid is non-refundable. Please keep this receipt for future reference.', 'receipt_copies' => '2', 'challan_bank' => '',
        'idcard_color' => '#1e40af', 'idcard_validity' => '', 'idcard_note' => 'If found, please return to the school.',
        'library_loan_days' => '14', 'library_fine_per_day' => '10', 'max_upload_mb' => '5',
        'email_enabled' => '0', 'email_from' => '', 'sms_enabled' => '0', 'sms_api_url' => '',
        'notify_fee_due' => '1', 'notify_absent' => '1', 'notify_results' => '1',
        'backup_keep' => '10',
    ];
    foreach ($defaults as $k => $v) {
        DB::q('INSERT IGNORE INTO settings (`key`,`value`) VALUES (?,?)', [$k, $v]);
    }

    if (!DB::val('SELECT COUNT(*) FROM academic_sessions')) {
        $y = (int)date('Y');
        $start = (int)date('n') >= 4 ? $y : $y - 1;
        DB::insert('academic_sessions', ['name' => $start . '-' . ($start + 1), 'start_date' => "$start-04-01", 'end_date' => ($start + 1) . '-03-31', 'is_current' => 1]);
    }

    $seedList = function (string $table, array $rows) {
        if (DB::val("SELECT COUNT(*) FROM `$table`")) {
            return;
        }
        foreach ($rows as $r) {
            DB::insert($table, $r);
        }
    };
    $seedList('fee_categories', [
        ['name' => 'Admission Fee', 'frequency' => 'one_time', 'is_system' => 1],
        ['name' => 'Monthly Tuition Fee', 'frequency' => 'monthly', 'is_system' => 1],
        ['name' => 'Annual Fee', 'frequency' => 'annual', 'is_system' => 1],
        ['name' => 'Examination Fee', 'frequency' => 'per_exam', 'is_system' => 1],
        ['name' => 'Transport Fee', 'frequency' => 'monthly', 'is_system' => 1, 'description' => 'Charged automatically from the student\'s assigned route'],
        ['name' => 'Computer Fee', 'frequency' => 'monthly', 'is_system' => 1],
        ['name' => 'Other Charges', 'frequency' => 'one_time', 'is_system' => 1],
    ]);
    $seedList('grades', [
        ['name' => 'A+', 'min_percent' => 80, 'max_percent' => 100, 'grade_point' => 4.00, 'remarks' => 'Outstanding', 'is_pass' => 1],
        ['name' => 'A', 'min_percent' => 70, 'max_percent' => 79.99, 'grade_point' => 3.70, 'remarks' => 'Excellent', 'is_pass' => 1],
        ['name' => 'B', 'min_percent' => 60, 'max_percent' => 69.99, 'grade_point' => 3.00, 'remarks' => 'Very Good', 'is_pass' => 1],
        ['name' => 'C', 'min_percent' => 50, 'max_percent' => 59.99, 'grade_point' => 2.50, 'remarks' => 'Good', 'is_pass' => 1],
        ['name' => 'D', 'min_percent' => 40, 'max_percent' => 49.99, 'grade_point' => 2.00, 'remarks' => 'Satisfactory', 'is_pass' => 1],
        ['name' => 'E', 'min_percent' => 33, 'max_percent' => 39.99, 'grade_point' => 1.00, 'remarks' => 'Pass', 'is_pass' => 1],
        ['name' => 'F', 'min_percent' => 0, 'max_percent' => 32.99, 'grade_point' => 0.00, 'remarks' => 'Fail', 'is_pass' => 0],
    ]);
    $seedList('periods', [
        ['name' => '1st Period', 'start_time' => '08:00', 'end_time' => '08:40', 'is_break' => 0, 'sort_order' => 1],
        ['name' => '2nd Period', 'start_time' => '08:40', 'end_time' => '09:20', 'is_break' => 0, 'sort_order' => 2],
        ['name' => '3rd Period', 'start_time' => '09:20', 'end_time' => '10:00', 'is_break' => 0, 'sort_order' => 3],
        ['name' => '4th Period', 'start_time' => '10:00', 'end_time' => '10:40', 'is_break' => 0, 'sort_order' => 4],
        ['name' => 'Break', 'start_time' => '10:40', 'end_time' => '11:10', 'is_break' => 1, 'sort_order' => 5],
        ['name' => '5th Period', 'start_time' => '11:10', 'end_time' => '11:50', 'is_break' => 0, 'sort_order' => 6],
        ['name' => '6th Period', 'start_time' => '11:50', 'end_time' => '12:30', 'is_break' => 0, 'sort_order' => 7],
        ['name' => '7th Period', 'start_time' => '12:30', 'end_time' => '13:10', 'is_break' => 0, 'sort_order' => 8],
        ['name' => '8th Period', 'start_time' => '13:10', 'end_time' => '14:00', 'is_break' => 0, 'sort_order' => 9],
    ]);
    $seedList('expense_categories', array_map(fn($n) => ['name' => $n], ['Utility Bills', 'School Maintenance', 'Stationery & Supplies', 'Transport & Fuel', 'Events & Functions', 'Other Expenses']));
    $seedList('income_categories', array_map(fn($n) => ['name' => $n], ['Donations', 'Canteen', 'Admission Forms', 'Library Fines', 'Other Income']));
    $seedList('book_categories', array_map(fn($n) => ['name' => $n], ['General', 'Science', 'Mathematics', 'Literature', 'Islamiyat', 'Urdu', 'Reference']));
    $seedList('departments', array_map(fn($n) => ['name' => $n], ['Academic', 'Administration', 'Accounts', 'Library', 'Transport', 'Support Staff']));
    if (!DB::val('SELECT COUNT(*) FROM designations')) {
        $dep = DB::pairs('SELECT name, id FROM departments');
        foreach ([['Principal', 'Administration'], ['Vice Principal', 'Administration'], ['Senior Teacher', 'Academic'], ['Teacher', 'Academic'], ['Accountant', 'Accounts'],
                     ['Clerk', 'Administration'], ['Receptionist', 'Administration'], ['Librarian', 'Library'], ['Driver', 'Transport'], ['Peon', 'Support Staff'], ['Security Guard', 'Support Staff']] as [$n, $dp]) {
            DB::insert('designations', ['name' => $n, 'department_id' => $dep[$dp] ?? null]);
        }
    }
}
