<?php
declare(strict_types=1);

final class AttendanceController
{
    private const MAP = ['P' => 'present', 'A' => 'absent', 'L' => 'leave', 'LT' => 'late'];

    /* ------------------------------------------------- student marking */
    public function students(): void
    {
        authorize('attendance.mark');
        $classId = input_int('class_id');
        $sectionId = input_int('section_id');
        $date = input_date('date', date('Y-m-d'));
        $students = [];
        $existing = [];
        if ($classId) {
            if ($sectionId && !Auth::canSection($sectionId)) {
                abort(403);
            }
            if (Auth::scoped() && !$sectionId) {
                throw new ValidationException(['section_id' => 'Please select a section.']);
            }
            $p = [$classId];
            $sql = "SELECT s.id, s.reg_no, s.roll_no, s.first_name, s.last_name, s.photo, s.section_id FROM students s WHERE s.status='active' AND s.class_id=?";
            if ($sectionId) {
                $sql .= ' AND s.section_id=?';
                $p[] = $sectionId;
            }
            $students = DB::all($sql . ' ORDER BY s.roll_no+0, s.first_name', $p);
            $ids = array_column($students, 'id');
            if ($ids) {
                foreach (DB::all('SELECT student_id, status, remarks FROM student_attendance WHERE att_date=? AND student_id IN ' . DB::in($ids), array_merge([$date], $ids)) as $r) {
                    $existing[$r['student_id']] = $r;
                }
            }
        }
        $holiday = DB::val("SELECT title FROM notices WHERE notice_type='holiday' AND publish_date<=? AND COALESCE(expiry_date, publish_date)>=?", [$date, $date]);
        view('attendance/students', compact('classId', 'sectionId', 'date', 'students', 'existing', 'holiday') + ['title' => t('Student Attendance')]);
    }

    public function save_students(): void
    {
        authorize('attendance.mark');
        $classId = input_int('class_id', 0);
        $sectionId = input_int('section_id');
        $date = input_date('date');
        if (!$date || $date > date('Y-m-d')) {
            throw new ValidationException(['date' => 'Attendance cannot be marked for a future date.']);
        }
        if (!Auth::canSection($sectionId)) {
            abort(403);
        }
        $statuses = (array)($_POST['att'] ?? []);
        $remarks = (array)($_POST['remarks'] ?? []);
        $valid = array_map('intval', DB::col("SELECT id FROM students WHERE status='active' AND class_id=?" . ($sectionId ? ' AND section_id=' . (int)$sectionId : ''), [$classId]));
        $absentees = [];
        $n = DB::tx(function () use ($statuses, $remarks, $valid, $date, $classId, &$absentees) {
            $n = 0;
            $sid = session_id_current();
            foreach ($statuses as $stuId => $code) {
                $stuId = (int)$stuId;
                if (!in_array($stuId, $valid, true) || !isset(self::MAP[$code])) {
                    continue;
                }
                $secId = DB::val('SELECT section_id FROM students WHERE id=?', [$stuId]);
                $prev = DB::val('SELECT status FROM student_attendance WHERE student_id=? AND att_date=?', [$stuId, $date]);
                DB::q('INSERT INTO student_attendance (student_id, session_id, class_id, section_id, att_date, status, remarks, marked_by) VALUES (?,?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE status=VALUES(status), remarks=VALUES(remarks), marked_by=VALUES(marked_by), class_id=VALUES(class_id), section_id=VALUES(section_id)',
                    [$stuId, $sid, $classId, $secId, $date, self::MAP[$code], mb_substr(trim((string)($remarks[$stuId] ?? '')), 0, 255) ?: null, Auth::id()]);
                if (self::MAP[$code] === 'absent' && $prev !== 'absent') {
                    $absentees[] = $stuId;
                }
                $n++;
            }
            return $n;
        });
        // Notify parents of absentees
        if ($absentees && setting('notify_absent', '1') === '1' && $date === date('Y-m-d')) {
            foreach (DB::all('SELECT s.first_name, s.last_name, s.user_id, p.user_id AS puid, p.father_phone FROM students s LEFT JOIN parents p ON p.id=s.parent_id WHERE s.id IN ' . DB::in($absentees), $absentees) as $s) {
                $msg = student_name($s) . ' is marked absent today (' . fmt_date($date) . ') at ' . setting('school_name') . '.';
                notify([$s['puid'], $s['user_id']], 'Absence alert', $msg, 'index.php?r=portal/attendance', 'attendance');
                if ($s['father_phone']) {
                    send_sms($s['father_phone'], $msg);
                }
            }
        }
        audit('mark', 'attendance', null, "Marked $n students for $date (class #$classId)");
        flash('success', sprintf(t('Attendance saved for %d students.'), $n));
        redirect(url('attendance/students', ['class_id' => $classId, 'section_id' => $sectionId, 'date' => $date]));
    }

    /* ------------------------------------------------- employee marking */
    public function employees(): void
    {
        authorize('attendance.employee');
        $type = input('type') === 'staff' ? 'staff' : 'teacher';
        $date = input_date('date', date('Y-m-d'));
        $table = $type === 'teacher' ? 'teachers' : 'staff';
        $rows = DB::all("SELECT t.id, t.employee_no, t.name, t.photo, g.name AS desig, a.status, a.check_in, a.check_out, a.remarks FROM $table t LEFT JOIN designations g ON g.id=t.designation_id
            LEFT JOIN employee_attendance a ON a.emp_type=? AND a.emp_id=t.id AND a.att_date=? WHERE t.status='active' ORDER BY t.name", [$type, $date]);
        view('attendance/employees', compact('type', 'date', 'rows') + ['title' => t('Employee Attendance')]);
    }

    public function save_employees(): void
    {
        authorize('attendance.employee');
        $type = input('type') === 'staff' ? 'staff' : 'teacher';
        $date = input_date('date');
        if (!$date || $date > date('Y-m-d')) {
            throw new ValidationException(['date' => 'Attendance cannot be marked for a future date.']);
        }
        $table = $type === 'teacher' ? 'teachers' : 'staff';
        $valid = array_map('intval', DB::col("SELECT id FROM $table WHERE status='active'"));
        $n = 0;
        DB::tx(function () use ($type, $date, $valid, &$n) {
            foreach ((array)($_POST['att'] ?? []) as $eid => $code) {
                $eid = (int)$eid;
                if (!in_array($eid, $valid, true) || !isset(self::MAP[$code])) {
                    continue;
                }
                $in = $_POST['check_in'][$eid] ?? '';
                $out = $_POST['check_out'][$eid] ?? '';
                DB::q('INSERT INTO employee_attendance (emp_type, emp_id, att_date, status, check_in, check_out, remarks, marked_by) VALUES (?,?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE status=VALUES(status), check_in=VALUES(check_in), check_out=VALUES(check_out), remarks=VALUES(remarks), marked_by=VALUES(marked_by)',
                    [$type, $eid, $date, self::MAP[$code], preg_match('/^\d{2}:\d{2}$/', $in) ? $in : null, preg_match('/^\d{2}:\d{2}$/', $out) ? $out : null,
                        mb_substr(trim((string)($_POST['remarks'][$eid] ?? '')), 0, 255) ?: null, Auth::id()]);
                $n++;
            }
        });
        audit('mark', 'employee_attendance', null, "Marked $n {$type}s for $date");
        flash('success', sprintf(t('Attendance saved for %d employees.'), $n));
        redirect(url('attendance/employees', ['type' => $type, 'date' => $date]));
    }

    /* ------------------------------------------------- reports */
    public function report(): void
    {
        authorize('attendance.view');
        $mode = (string)input('mode', 'monthly');
        $month = valid_month((string)input('month')) ? (string)input('month') : date('Y-m');
        $classId = input_int('class_id');
        $sectionId = input_int('section_id');
        $date = input_date('date', date('Y-m-d'));
        $data = compact('mode', 'month', 'classId', 'sectionId', 'date') + ['title' => t('Attendance Reports')];

        if ($mode === 'daily') {
            // Class-wise summary for a date
            $sql = "SELECT c.id AS class_id, c.name AS class_name, sec.id AS section_id, sec.name AS section_name,
                    (SELECT COUNT(*) FROM students s WHERE s.section_id=sec.id AND s.status='active') AS strength,
                    SUM(a.status='present') AS p, SUM(a.status='absent') AS a, SUM(a.status='leave') AS l, SUM(a.status='late') AS lt
                FROM sections sec JOIN classes c ON c.id=sec.class_id LEFT JOIN student_attendance a ON a.section_id=sec.id AND a.att_date=?";
            $p = [$date];
            if (Auth::scoped()) {
                $ids = Auth::teacherSectionIds();
                $sql .= ' WHERE sec.id IN ' . DB::in($ids);
                $p = array_merge($p, $ids);
            }
            $data['daily'] = DB::all($sql . ' GROUP BY sec.id ORDER BY c.numeric_order, sec.name', $p);
        } elseif ($mode === 'employees') {
            $type = input('type') === 'staff' ? 'staff' : 'teacher';
            $data['type'] = $type;
            $data += $this->employeeRegister($type, $month);
        } elseif ($classId) {
            if ($sectionId && !Auth::canSection($sectionId)) {
                abort(403);
            }
            $data += $this->studentRegister($classId, $sectionId, $month);
        }
        if (input('print')) {
            $data['landscape'] = true;
            $data['subtitle'] = $this->subtitle($data);
            view('attendance/report_print', $data, 'print');
        }
        if (input('export')) {
            $this->export($data);
        }
        view('attendance/report', $data);
    }

    private function subtitle(array $d): string
    {
        if ($d['mode'] === 'daily') {
            return t('Daily summary') . ' — ' . fmt_date($d['date']);
        }
        if ($d['mode'] === 'employees') {
            return t(ucfirst($d['type'])) . ' — ' . fmt_month($d['month']);
        }
        return DB::val('SELECT name FROM classes WHERE id=?', [$d['classId']]) . ' ' . ($d['sectionId'] ? DB::val('SELECT name FROM sections WHERE id=?', [$d['sectionId']]) : '') . ' — ' . fmt_month($d['month']);
    }

    private function studentRegister(int $classId, ?int $sectionId, string $month): array
    {
        $p = [$classId];
        $sql = "SELECT s.id, s.reg_no, s.roll_no, s.first_name, s.last_name FROM students s WHERE s.status='active' AND s.class_id=?";
        if ($sectionId) {
            $sql .= ' AND s.section_id=?';
            $p[] = $sectionId;
        } elseif (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $sql .= ' AND s.section_id IN ' . DB::in($ids);
            $p = array_merge($p, $ids);
        }
        $students = DB::all($sql . ' ORDER BY s.roll_no+0, s.first_name', $p);
        $days = (int)date('t', strtotime($month . '-01'));
        $grid = [];
        $ids = array_column($students, 'id');
        if ($ids) {
            foreach (DB::all("SELECT student_id, DAY(att_date) AS d, status FROM student_attendance WHERE DATE_FORMAT(att_date,'%Y-%m')=? AND student_id IN " . DB::in($ids), array_merge([$month], $ids)) as $r) {
                $grid[$r['student_id']][(int)$r['d']] = $r['status'];
            }
        }
        return ['people' => $students, 'grid' => $grid, 'days' => $days];
    }

    private function employeeRegister(string $type, string $month): array
    {
        $table = $type === 'teacher' ? 'teachers' : 'staff';
        $people = DB::all("SELECT id, employee_no AS reg_no, name FROM $table WHERE status='active' ORDER BY name");
        $grid = [];
        foreach (DB::all("SELECT emp_id, DAY(att_date) AS d, status FROM employee_attendance WHERE emp_type=? AND DATE_FORMAT(att_date,'%Y-%m')=?", [$type, $month]) as $r) {
            $grid[$r['emp_id']][(int)$r['d']] = $r['status'];
        }
        return ['people' => $people, 'grid' => $grid, 'days' => (int)date('t', strtotime($month . '-01'))];
    }

    private function export(array $d): never
    {
        $codes = ['present' => 'P', 'absent' => 'A', 'leave' => 'L', 'late' => 'LT'];
        if ($d['mode'] === 'daily') {
            $rows = array_map(fn($r) => [$r['class_name'], $r['section_name'], $r['strength'], (int)$r['p'], (int)$r['a'], (int)$r['l'], (int)$r['lt']], $d['daily']);
            export_table('attendance_daily_' . $d['date'], ['Class', 'Section', 'Strength', 'Present', 'Absent', 'Leave', 'Late'], $rows, input('export') === 'xls' ? 'xls' : 'csv');
        }
        $head = ['ID', 'Name'];
        for ($i = 1; $i <= ($d['days'] ?? 0); $i++) {
            $head[] = (string)$i;
        }
        array_push($head, 'P', 'A', 'L', 'LT', '%');
        $rows = [];
        foreach ($d['people'] ?? [] as $p) {
            $g = $d['grid'][$p['id']] ?? [];
            $line = [$p['reg_no'], $p['name'] ?? student_name($p)];
            for ($i = 1; $i <= $d['days']; $i++) {
                $line[] = isset($g[$i]) ? $codes[$g[$i]] : '';
            }
            $c = array_count_values($g);
            $tot = count($g);
            array_push($line, $c['present'] ?? 0, $c['absent'] ?? 0, $c['leave'] ?? 0, $c['late'] ?? 0, $tot ? round((($c['present'] ?? 0) + ($c['late'] ?? 0)) / $tot * 100, 1) : '');
            $rows[] = $line;
        }
        export_table('attendance_' . $d['month'], $head, $rows, input('export') === 'xls' ? 'xls' : 'csv');
    }
}
