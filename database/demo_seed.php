<?php
declare(strict_types=1);

/**
 * OPTIONAL sample data for evaluation / training. Never run on a live school database.
 * Usage: chosen in the installer, or `php database/demo_seed.php` after installation.
 */
function run_demo_seed(): void
{
    if ((int)DB::val('SELECT COUNT(*) FROM students') > 0) {
        return; // never mix with real data
    }
    mt_srand(2026);
    $sid = (int)DB::val('SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1');
    $sess = DB::row('SELECT * FROM academic_sessions WHERE id=?', [$sid]);
    $pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
    $boys = ['Muhammad Ali', 'Ahmed', 'Hassan', 'Usman', 'Bilal', 'Hamza', 'Zain', 'Abdullah', 'Saad', 'Talha', 'Haris', 'Fahad', 'Umar', 'Ibrahim', 'Arsalan', 'Shayan', 'Rayyan', 'Danish'];
    $girls = ['Ayesha', 'Fatima', 'Zainab', 'Maryam', 'Hira', 'Iqra', 'Sana', 'Areeba', 'Hafsa', 'Laiba', 'Eman', 'Mahnoor', 'Noor', 'Alishba', 'Dua', 'Khadija', 'Anaya', 'Minahil'];
    $last = ['Khan', 'Ahmed', 'Butt', 'Malik', 'Qureshi', 'Sheikh', 'Chaudhry', 'Raza', 'Hussain', 'Iqbal', 'Siddiqui', 'Javed', 'Mirza', 'Abbasi'];
    $fathers = ['Imran', 'Tariq', 'Kashif', 'Naveed', 'Asif', 'Shahid', 'Khalid', 'Zahid', 'Rashid', 'Nadeem', 'Sajid', 'Waqas', 'Faisal', 'Amjad'];
    $areas = ['Model Town', 'Johar Town', 'Gulberg', 'Iqbal Town', 'Township', 'Wapda Town', 'Faisal Town', 'Garden Town'];

    DB::tx(function () use ($sid, $sess, $pick, $boys, $girls, $last, $fathers, $areas) {
        // Academic structure
        $classNames = ['Nursery', 'Prep', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8'];
        $classes = [];
        foreach ($classNames as $i => $n) {
            $classes[$n] = DB::insert('classes', ['name' => $n, 'numeric_order' => $i + 1]);
        }
        $subjectNames = ['English' => 'ENG', 'Urdu' => 'URD', 'Mathematics' => 'MTH', 'General Science' => 'SCI', 'Islamiyat' => 'ISL', 'Social Studies' => 'SST', 'Computer' => 'CMP', 'Nazra Quran' => 'QRN'];
        $subjects = [];
        foreach ($subjectNames as $n => $c) {
            $subjects[$n] = DB::insert('subjects', ['name' => $n, 'code' => $c, 'type' => $n === 'Computer' ? 'both' : 'theory']);
        }
        // Teachers & staff
        $deptAcad = (int)DB::val("SELECT id FROM departments WHERE name='Academic'");
        $desTeacher = (int)DB::val("SELECT id FROM designations WHERE name='Teacher'");
        $teacherRole = (int)DB::val("SELECT id FROM roles WHERE slug='teacher'");
        $tNames = [['Sadia Parveen', 'female', 'M.A English, B.Ed', 'English'], ['Muhammad Asif', 'male', 'M.Sc Mathematics', 'Mathematics'], ['Rabia Naz', 'female', 'M.A Urdu', 'Urdu'],
            ['Kamran Haider', 'male', 'M.Sc Physics, B.Ed', 'General Science'], ['Hafiz Abdul Rehman', 'male', 'M.A Islamiyat', 'Islamiyat'], ['Nida Farooq', 'female', 'BS Computer Science', 'Computer'],
            ['Samina Akhtar', 'female', 'M.A Education', 'Social Studies'], ['Bushra Iqbal', 'female', 'B.A, PTC', 'English']];
        $teachers = [];
        foreach ($tNames as $i => [$n, $g, $q, $spec]) {
            $id = DB::insert('teachers', ['employee_no' => next_number('teacher'), 'name' => $n, 'gender' => $g, 'qualification' => $q, 'specialization' => $spec, 'experience_years' => mt_rand(2, 15),
                'phone' => '03' . mt_rand(0, 4) . mt_rand(1, 9) . '-' . mt_rand(1000000, 9999999), 'joining_date' => date('Y-m-d', strtotime('-' . mt_rand(1, 8) . ' years')), 'department_id' => $deptAcad, 'designation_id' => $desTeacher,
                'basic_salary' => mt_rand(35, 70) * 1000, 'allowances' => 5000, 'cnic' => '35202-' . mt_rand(1000000, 9999999) . '-' . mt_rand(1, 9), 'status' => 'active']);
            $teachers[] = ['id' => $id, 'spec' => $spec];
            $uid = DB::insert('users', ['role_id' => $teacherRole, 'name' => $n, 'username' => 'teacher' . ($i + 1), 'password' => password_hash('Teacher@123', PASSWORD_DEFAULT), 'status' => 'active']);
            DB::update('teachers', ['user_id' => $uid], 'id=?', [$id]);
        }
        $staffList = [['Zafar Iqbal', 'Accountant', 'accountant'], ['Shazia Bano', 'Receptionist', 'receptionist'], ['Abdul Ghafoor', 'Driver', null], ['Nasreen Akhtar', 'Librarian', 'librarian']];
        foreach ($staffList as [$n, $des, $role]) {
            $id = DB::insert('staff', ['employee_no' => next_number('staff'), 'name' => $n, 'gender' => in_array($n, ['Shazia Bano', 'Nasreen Akhtar'], true) ? 'female' : 'male', 'designation_id' => DB::val('SELECT id FROM designations WHERE name=?', [$des]),
                'department_id' => DB::val('SELECT department_id FROM designations WHERE name=?', [$des]), 'basic_salary' => mt_rand(25, 45) * 1000, 'allowances' => 3000, 'joining_date' => date('Y-m-d', strtotime('-3 years')),
                'phone' => '0300-' . mt_rand(1000000, 9999999), 'status' => 'active']);
            if ($role) {
                $uid = DB::insert('users', ['role_id' => DB::val('SELECT id FROM roles WHERE slug=?', [$role]), 'name' => $n, 'username' => $role, 'password' => password_hash('Staff@123', PASSWORD_DEFAULT), 'status' => 'active']);
                DB::update('staff', ['user_id' => $uid], 'id=?', [$id]);
            }
        }
        // Sections, subject assignment
        $sections = [];
        $ti = 0;
        foreach ($classes as $cn => $cid) {
            foreach (['A', 'B'] as $si => $sn) {
                if ($si === 1 && !in_array($cn, ['Class 1', 'Class 5', 'Class 8'], true)) {
                    continue;
                }
                $secId = DB::insert('sections', ['class_id' => $cid, 'name' => $sn, 'capacity' => 35, 'room' => 'R-' . (count($sections) + 1), 'class_teacher_id' => $teachers[$ti % count($teachers)]['id']]);
                $ti++;
                $sections[] = ['id' => $secId, 'class_id' => $cid, 'class' => $cn];
                $subs = in_array($cn, ['Nursery', 'Prep'], true) ? ['English', 'Urdu', 'Mathematics', 'Nazra Quran'] : ['English', 'Urdu', 'Mathematics', 'General Science', 'Islamiyat', 'Social Studies', 'Computer'];
                foreach ($subs as $sb) {
                    $t = array_values(array_filter($teachers, fn($x) => $x['spec'] === $sb))[0]['id'] ?? $teachers[0]['id'];
                    DB::insert('class_subjects', ['class_id' => $cid, 'section_id' => $secId, 'subject_id' => $subjects[$sb], 'teacher_id' => $t]);
                }
            }
        }
        // Fee structure
        $cats = DB::pairs('SELECT name, id FROM fee_categories');
        foreach ($classes as $cn => $cid) {
            $ord = array_search($cn, array_keys($classes), true);
            $tuition = 2500 + $ord * 300;
            foreach (['Admission Fee' => 5000, 'Monthly Tuition Fee' => $tuition, 'Annual Fee' => 4000, 'Examination Fee' => 800, 'Computer Fee' => $ord >= 2 ? 500 : 0] as $cat => $amt) {
                if ($amt > 0) {
                    DB::insert('fee_structures', ['session_id' => $sid, 'class_id' => $cid, 'fee_category_id' => $cats[$cat], 'amount' => $amt]);
                }
            }
        }
        // Transport
        $v1 = DB::insert('transport_vehicles', ['vehicle_no' => 'LEB-4521', 'model' => 'Toyota Hiace 2018', 'capacity' => 15, 'driver_name' => 'Abdul Ghafoor', 'driver_phone' => '0301-4455667', 'status' => 'active']);
        $v2 = DB::insert('transport_vehicles', ['vehicle_no' => 'LES-9087', 'model' => 'Toyota Coaster 2020', 'capacity' => 28, 'driver_name' => 'Muhammad Riaz', 'driver_phone' => '0322-7788990', 'status' => 'active']);
        $routes = [DB::insert('transport_routes', ['name' => 'Route 1 - Model Town', 'start_point' => 'Model Town Link Road', 'end_point' => 'School', 'stops' => 'Model Town, Faisal Town, Garden Town', 'vehicle_id' => $v1, 'monthly_fee' => 3000]),
            DB::insert('transport_routes', ['name' => 'Route 2 - Johar Town', 'start_point' => 'Expo Centre', 'end_point' => 'School', 'stops' => 'Johar Town, Wapda Town, Township', 'vehicle_id' => $v2, 'monthly_fee' => 3500])];
        // Students
        $parentRole = (int)DB::val("SELECT id FROM roles WHERE slug='parent'");
        $studentRole = (int)DB::val("SELECT id FROM roles WHERE slug='student'");
        $allStudents = [];
        $n = 0;
        foreach ($sections as $sec) {
            $count = mt_rand(7, 10);
            for ($k = 1; $k <= $count; $k++) {
                $n++;
                $male = mt_rand(0, 1) === 1;
                $ln = $pick($last);
                $fname = $pick($fathers) . ' ' . $ln;
                $pid = DB::insert('parents', ['father_name' => $fname, 'father_cnic' => '35202-' . (1000000 + $n * 7919 % 8999999) . '-' . ($n % 9 + 1), 'father_phone' => '03' . mt_rand(0, 4) . mt_rand(0, 9) . '-' . mt_rand(1000000, 9999999),
                    'father_occupation' => $pick(['Business', 'Government Servant', 'Engineer', 'Doctor', 'Teacher', 'Shopkeeper']), 'mother_name' => $pick($girls) . ' Bibi', 'address' => 'House ' . mt_rand(1, 400) . ', ' . $pick($areas) . ', Lahore']);
                $ord = array_search($sec['class'], array_keys($classes), true);
                $age = 3 + $ord;
                $adm = date('Y-m-d', strtotime($sess['start_date'] . ' -' . mt_rand(0, 3) . ' years +' . mt_rand(0, 20) . ' days'));
                if ($adm > date('Y-m-d')) {
                    $adm = date('Y-m-d', strtotime('-' . mt_rand(5, 60) . ' days'));
                }
                $stuId = DB::insert('students', ['reg_no' => next_number('student'), 'roll_no' => (string)$k, 'parent_id' => $pid, 'first_name' => $male ? $pick($boys) : $pick($girls), 'last_name' => $ln,
                    'gender' => $male ? 'male' : 'female', 'dob' => date('Y-m-d', strtotime("-$age years -" . mt_rand(10, 360) . ' days')), 'religion' => 'Islam', 'nationality' => 'Pakistani',
                    'blood_group' => $pick(['A+', 'B+', 'O+', 'AB+', 'O-']), 'address' => 'House ' . mt_rand(1, 400) . ', ' . $pick($areas), 'city' => 'Lahore', 'admission_date' => $adm,
                    'session_id' => $sid, 'class_id' => $sec['class_id'], 'section_id' => $sec['id'], 'route_id' => mt_rand(1, 5) === 1 ? $pick($routes) : null, 'status' => 'active']);
                DB::insert('admissions', ['student_id' => $stuId, 'session_id' => $sid, 'class_id' => $sec['class_id'], 'section_id' => $sec['id'], 'admission_date' => $adm]);
                DB::insert('student_history', ['student_id' => $stuId, 'session_id' => $sid, 'class_id' => $sec['class_id'], 'section_id' => $sec['id'], 'action' => 'admission', 'details' => 'Admitted in ' . $sec['class']]);
                $allStudents[] = ['id' => $stuId, 'class_id' => $sec['class_id'], 'section_id' => $sec['id']];
                if ($n === 1) { // demo portal logins
                    $u = DB::insert('users', ['role_id' => $parentRole, 'name' => $fname, 'username' => 'parent', 'password' => password_hash('Parent@123', PASSWORD_DEFAULT), 'status' => 'active']);
                    DB::update('parents', ['user_id' => $u], 'id=?', [$pid]);
                    $u = DB::insert('users', ['role_id' => $studentRole, 'name' => 'Demo Student', 'username' => 'student', 'password' => password_hash('Student@123', PASSWORD_DEFAULT), 'status' => 'active']);
                    DB::update('students', ['user_id' => $u], 'id=?', [$stuId]);
                }
            }
        }
        // Discounts
        foreach (array_slice($allStudents, 3, 4) as $i => $s) {
            DB::insert('student_discounts', ['student_id' => $s['id'], 'title' => $i % 2 ? 'Sibling Discount' : 'Merit Scholarship', 'discount_type' => $i % 2 ? 'discount' : 'scholarship', 'value_type' => 'percent', 'value' => $i % 2 ? 10 : 50, 'fee_category_id' => $cats['Monthly Tuition Fee']]);
        }
        // Attendance: last 12 school days
        $days = [];
        for ($d = 1; count($days) < 12 && $d < 40; $d++) {
            $date = date('Y-m-d', strtotime("-$d days"));
            if (in_array((int)date('N', strtotime($date)), school_days(), true) && $date >= $sess['start_date']) {
                $days[] = $date;
            }
        }
        $days[] = date('Y-m-d');
        foreach ($days as $date) {
            foreach ($allStudents as $s) {
                $r = mt_rand(1, 100);
                DB::insert('student_attendance', ['student_id' => $s['id'], 'session_id' => $sid, 'class_id' => $s['class_id'], 'section_id' => $s['section_id'], 'att_date' => $date, 'status' => $r <= 86 ? 'present' : ($r <= 93 ? 'absent' : ($r <= 97 ? 'late' : 'leave'))]);
            }
            foreach (DB::col("SELECT id FROM teachers") as $tid) {
                DB::insert('employee_attendance', ['emp_type' => 'teacher', 'emp_id' => $tid, 'att_date' => $date, 'status' => mt_rand(1, 20) === 1 ? 'absent' : 'present', 'check_in' => '07:5' . mt_rand(0, 9)]);
            }
        }
    });

    // Fees: previous and current month invoices with payments (uses services → own transactions)
    $classIds = array_map('intval', DB::col('SELECT id FROM classes'));
    $prev = date('Y-m', strtotime('first day of -1 month'));
    FeeService::generateMonthly($prev, $classIds, [], date('Y-m-01', strtotime($prev . '-01')), $prev . '-10');
    FeeService::generateMonthly(date('Y-m'), $classIds, [], date('Y-m-01'), date('Y-m-10'));
    foreach (DB::all("SELECT * FROM fee_invoices WHERE fee_month=?", [$prev]) as $i => $inv) {
        if ($i % 6 !== 0) {
            FeeService::pay((int)$inv['id'], (float)$inv['net_amount'], 0, $i % 4 ? 'cash' : 'bank', null, date('Y-m-d', strtotime($prev . '-0' . (1 + $i % 9))), null);
        }
    }
    foreach (DB::all("SELECT * FROM fee_invoices WHERE fee_month=?", [date('Y-m')]) as $i => $inv) {
        if ($i % 3 === 0) {
            FeeService::pay((int)$inv['id'], $i % 9 === 0 ? round((float)$inv['net_amount'] / 2) : (float)$inv['net_amount'], 0, 'cash', null, date('Y-m-d', strtotime('-' . ($i % 5) . ' days')) < date('Y-m-01') ? date('Y-m-01') : date('Y-m-d', strtotime('-' . ($i % 5) . ' days')), null);
        }
    }

    DB::tx(function () use ($sid) {
        // Exam with marks
        $examId = DB::insert('exams', ['session_id' => $sid, 'name' => 'First Term Examination', 'exam_type' => 'midterm', 'start_date' => date('Y-m-d', strtotime('-30 days')), 'end_date' => date('Y-m-d', strtotime('-22 days')), 'is_published' => 1]);
        DB::insert('exams', ['session_id' => $sid, 'name' => 'Monthly Test - ' . date('F'), 'exam_type' => 'monthly_test', 'start_date' => date('Y-m-d', strtotime('+7 days')), 'end_date' => date('Y-m-d', strtotime('+9 days'))]);
        foreach (DB::all('SELECT DISTINCT class_id, subject_id FROM class_subjects') as $k => $cs) {
            $esId = DB::insert('exam_subjects', ['exam_id' => $examId, 'class_id' => $cs['class_id'], 'subject_id' => $cs['subject_id'], 'exam_date' => date('Y-m-d', strtotime('-' . (30 - $k % 7) . ' days')), 'start_time' => '08:30', 'total_marks' => 100, 'pass_marks' => 33]);
            foreach (DB::col("SELECT id FROM students WHERE class_id=? AND status='active'", [$cs['class_id']]) as $stu) {
                $absent = mt_rand(1, 60) === 1;
                DB::insert('marks', ['exam_subject_id' => $esId, 'student_id' => $stu, 'obtained_marks' => $absent ? null : mt_rand(28, 98), 'is_absent' => $absent ? 1 : 0]);
            }
        }
        foreach (DB::col('SELECT id FROM classes') as $cid) {
            if (DB::val('SELECT COUNT(*) FROM exam_subjects WHERE exam_id=? AND class_id=?', [$examId, $cid])) {
                ResultService::generate($examId, (int)$cid);
            }
        }
        // Timetable for each section
        $periods = DB::col('SELECT id FROM periods WHERE is_break=0 ORDER BY sort_order');
        foreach (DB::all('SELECT id FROM sections') as $sec) {
            $subs = DB::all('SELECT subject_id, teacher_id FROM class_subjects WHERE section_id=?', [$sec['id']]);
            foreach (school_days() as $day) {
                foreach ($periods as $pi => $pid) {
                    $s = $subs[($pi + $day) % count($subs)];
                    $busy = DB::val('SELECT id FROM timetables WHERE session_id=? AND teacher_id=? AND day_of_week=? AND period_id=?', [$sid, $s['teacher_id'], $day, $pid]);
                    DB::insert('timetables', ['session_id' => $sid, 'section_id' => $sec['id'], 'day_of_week' => $day, 'period_id' => $pid, 'subject_id' => $s['subject_id'], 'teacher_id' => $busy ? null : $s['teacher_id']]);
                }
            }
        }
        // Library
        $bc = DB::pairs('SELECT name, id FROM book_categories');
        foreach ([['Oxford English Grammar', 'Oxford Press', 'General'], ['Fun with Science', 'Paramount', 'Science'], ['Basic Mathematics', 'Gaba Publishers', 'Mathematics'], ['Bang-e-Dra', 'Allama Iqbal', 'Urdu'],
                     ['Seerat-un-Nabi (PBUH)', 'Shibli Nomani', 'Islamiyat'], ['The Jungle Book', 'Rudyard Kipling', 'Literature'], ['Oxford Atlas for Pakistan', 'Oxford Press', 'Reference'], ['Computer Studies 6', 'Paramount', 'Science']] as $i => [$tt, $au, $cat]) {
            $q = mt_rand(2, 6);
            DB::insert('books', ['title' => $tt, 'author' => $au, 'publisher' => $au, 'category_id' => $bc[$cat] ?? null, 'isbn' => '978969' . str_pad((string)(1000 + $i * 37), 7, '0', STR_PAD_LEFT), 'quantity' => $q, 'available' => $q, 'price' => mt_rand(3, 15) * 100, 'rack_no' => 'R' . ($i % 4 + 1)]);
        }
        foreach (array_slice(DB::col("SELECT id FROM students ORDER BY id"), 0, 4) as $i => $stu) {
            $book = (int)DB::val('SELECT id FROM books ORDER BY id LIMIT 1 OFFSET ' . $i);
            DB::insert('book_issues', ['book_id' => $book, 'member_type' => 'student', 'member_id' => $stu, 'issue_date' => date('Y-m-d', strtotime('-' . (5 + $i * 6) . ' days')), 'due_date' => date('Y-m-d', strtotime('-' . (5 + $i * 6) . ' days +14 days')), 'status' => 'issued']);
            DB::q('UPDATE books SET available=available-1 WHERE id=?', [$book]);
        }
        // Finance
        $ec = DB::pairs('SELECT name, id FROM expense_categories');
        foreach ([['Electricity bill (LESCO)', 'Utility Bills', 38500], ['Gas bill (SNGPL)', 'Utility Bills', 6200], ['PTCL internet', 'Utility Bills', 4500], ['Classroom fans repair', 'School Maintenance', 12000],
                     ['Whiteboard markers & chalk', 'Stationery & Supplies', 5400], ['Coaster diesel', 'Transport & Fuel', 22000]] as $i => [$tt, $cat, $amt]) {
            DB::insert('expenses', ['category_id' => $ec[$cat], 'title' => $tt, 'amount' => $amt, 'expense_date' => date('Y-m-d', strtotime('-' . ($i * 5 + 1) . ' days')), 'method' => $i % 2 ? 'cash' : 'bank', 'paid_to' => explode(' ', $tt)[0]]);
        }
        $ic = DB::pairs('SELECT name, id FROM income_categories');
        DB::insert('incomes', ['category_id' => $ic['Canteen'], 'title' => 'Canteen contract rent', 'amount' => 15000, 'income_date' => date('Y-m-d', strtotime('-6 days')), 'method' => 'cash']);
        DB::insert('incomes', ['category_id' => $ic['Admission Forms'], 'title' => 'Admission form sale', 'amount' => 4500, 'income_date' => date('Y-m-d', strtotime('-12 days')), 'method' => 'cash']);
        // Notices
        DB::insert('notices', ['title' => 'Winter Vacation Schedule', 'body' => 'The school will remain closed for winter vacation from 22 December to 31 December. Classes resume on 1 January.', 'notice_type' => 'holiday', 'audience' => 'all', 'publish_date' => date('Y-m-d', strtotime('-2 days'))]);
        DB::insert('notices', ['title' => 'Parent-Teacher Meeting', 'body' => 'PTM for all classes will be held on Saturday from 9:00 AM to 12:00 PM. Parents are requested to attend.', 'notice_type' => 'event', 'audience' => 'parents', 'publish_date' => date('Y-m-d', strtotime('-1 days'))]);
        DB::insert('notices', ['title' => 'Monthly Test Schedule', 'body' => 'Monthly tests will start next week. Date sheet has been shared with students.', 'notice_type' => 'exam', 'audience' => 'students', 'publish_date' => date('Y-m-d')]);
        // Homework
        $sec = DB::row('SELECT cs.* FROM class_subjects cs ORDER BY cs.id LIMIT 1');
        DB::insert('homework', ['session_id' => $sid, 'class_id' => $sec['class_id'], 'section_id' => $sec['section_id'], 'subject_id' => $sec['subject_id'], 'teacher_id' => $sec['teacher_id'], 'title' => 'Write 10 sentences about My School',
            'description' => 'Use neat handwriting in your notebook.', 'assign_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+2 days')), 'max_marks' => 10]);
        // Leave
        DB::insert('leaves', ['emp_type' => 'teacher', 'emp_id' => DB::val('SELECT id FROM teachers ORDER BY id LIMIT 1'), 'leave_type' => 'sick', 'from_date' => date('Y-m-d', strtotime('+1 day')), 'to_date' => date('Y-m-d', strtotime('+2 days')), 'days' => 2, 'reason' => 'Medical checkup', 'status' => 'pending']);
    });
    // Salaries for previous month (paid)
    $prev = date('Y-m', strtotime('first day of -1 month'));
    foreach (['teacher' => 'teachers', 'staff' => 'staff'] as $type => $table) {
        foreach (DB::all("SELECT * FROM $table") as $e) {
            DB::insert('salaries', ['slip_no' => next_number('salary'), 'emp_type' => $type, 'emp_id' => $e['id'], 'salary_month' => $prev, 'basic' => $e['basic_salary'], 'allowances' => $e['allowances'],
                'net_salary' => $e['basic_salary'] + $e['allowances'], 'status' => 'paid', 'paid_on' => date('Y-m-05'), 'method' => 'bank']);
        }
    }
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require dirname(__DIR__) . '/app/bootstrap.php';
    run_demo_seed();
    echo "Demo data loaded.\n";
}
