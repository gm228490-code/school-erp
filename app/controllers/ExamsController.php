<?php
declare(strict_types=1);

final class ExamsController
{
    private function exams(): array
    {
        return DB::pairs('SELECT id, name FROM exams WHERE session_id=? ORDER BY COALESCE(start_date, created_at) DESC', [session_id_current()]);
    }

    private function exam(int $id): array
    {
        $e = DB::row('SELECT e.*, s.name AS session_name FROM exams e JOIN academic_sessions s ON s.id=e.session_id WHERE e.id=?', [$id]);
        if (!$e) {
            abort(404);
        }
        return $e;
    }

    /* ------------------------------------------------- schedule */
    public function schedule(): void
    {
        authorize('exams.manage');
        $examId = input_int('exam_id');
        $classId = input_int('class_id');
        $rows = [];
        if ($examId && $classId) {
            $existing = [];
            foreach (DB::all('SELECT * FROM exam_subjects WHERE exam_id=? AND class_id=?', [$examId, $classId]) as $r) {
                $existing[$r['subject_id']] = $r;
            }
            foreach (subject_options($classId) as $sid => $name) {
                $rows[] = ['subject_id' => $sid, 'name' => $name, 'row' => $existing[$sid] ?? null];
            }
        }
        $overview = $examId ? DB::all('SELECT c.name, COUNT(es.id) AS n, MIN(es.exam_date) AS first, MAX(es.exam_date) AS last FROM exam_subjects es JOIN classes c ON c.id=es.class_id WHERE es.exam_id=? GROUP BY c.id ORDER BY c.numeric_order', [$examId]) : [];
        view('exams/schedule', ['exams' => $this->exams(), 'examId' => $examId, 'classId' => $classId, 'rows' => $rows, 'overview' => $overview, 'title' => t('Exam Schedule')]);
    }

    public function save_schedule(): void
    {
        authorize('exams.manage');
        $examId = input_int('exam_id', 0);
        $classId = input_int('class_id', 0);
        $this->exam($examId);
        $n = 0;
        DB::tx(function () use ($examId, $classId, &$n) {
            foreach ((array)($_POST['sub'] ?? []) as $subId => $d) {
                $subId = (int)$subId;
                if (empty($d['include'])) {
                    $esId = DB::val('SELECT id FROM exam_subjects WHERE exam_id=? AND class_id=? AND subject_id=?', [$examId, $classId, $subId]);
                    if ($esId && !DB::val('SELECT COUNT(*) FROM marks WHERE exam_subject_id=?', [$esId])) {
                        DB::delete('exam_subjects', 'id=?', [$esId]);
                    }
                    continue;
                }
                $total = (float)($d['total'] ?? 100);
                $pass = (float)($d['pass'] ?? 33);
                if ($total <= 0 || $pass < 0 || $pass > $total) {
                    throw new ValidationException(['sub' => 'Pass marks must be between 0 and total marks, and total must be positive.']);
                }
                DB::upsert('exam_subjects', [
                    'exam_id' => $examId, 'class_id' => $classId, 'subject_id' => $subId,
                    'exam_date' => valid_date($d['date'] ?? '') ? $d['date'] : null, 'start_time' => preg_match('/^\d{2}:\d{2}$/', $d['time'] ?? '') ? $d['time'] : null,
                    'total_marks' => $total, 'pass_marks' => $pass,
                ], ['exam_date', 'start_time', 'total_marks', 'pass_marks']);
                $n++;
            }
        });
        audit('schedule', 'exams', (string)$examId, "Saved $n subjects for class #$classId");
        flash('success', t('Exam schedule saved.'));
        redirect(url('exams/schedule', ['exam_id' => $examId, 'class_id' => $classId]));
    }

    public function datesheet(): void
    {
        authorize('exams.view');
        $exam = $this->exam(input_int('exam_id', 0));
        $p = [$exam['id']];
        $sql = 'SELECT es.*, c.name AS class_name, sb.name AS subject FROM exam_subjects es JOIN classes c ON c.id=es.class_id JOIN subjects sb ON sb.id=es.subject_id WHERE es.exam_id=?';
        if ($cid = input_int('class_id')) {
            $sql .= ' AND es.class_id=?';
            $p[] = $cid;
        }
        $rows = DB::all($sql . ' ORDER BY c.numeric_order, es.exam_date, es.start_time', $p);
        view('exams/datesheet', ['exam' => $exam, 'rows' => $rows, 'title' => t('Date Sheet') . ' — ' . $exam['name']], 'print');
    }

    /* ------------------------------------------------- marks entry */
    public function marks(): void
    {
        authorize('exams.marks');
        $examId = input_int('exam_id');
        $classId = input_int('class_id');
        $sectionId = input_int('section_id');
        $subjectId = input_int('subject_id');
        $es = null;
        $students = [];
        $marks = [];
        if ($examId && $classId && $subjectId) {
            $es = DB::row('SELECT es.*, sb.name AS subject, e.is_published FROM exam_subjects es JOIN subjects sb ON sb.id=es.subject_id JOIN exams e ON e.id=es.exam_id WHERE es.exam_id=? AND es.class_id=? AND es.subject_id=?', [$examId, $classId, $subjectId]);
            if ($es) {
                if (Auth::scoped() && (!$sectionId || !Auth::canSubject($sectionId, $subjectId))) {
                    abort(403, t('You can only enter marks for your assigned sections and subjects.'));
                }
                $p = [$classId];
                $sql = "SELECT id, reg_no, roll_no, first_name, last_name FROM students WHERE status='active' AND class_id=?";
                if ($sectionId) {
                    $sql .= ' AND section_id=?';
                    $p[] = $sectionId;
                }
                $students = DB::all($sql . ' ORDER BY roll_no+0, first_name', $p);
                foreach (DB::all('SELECT * FROM marks WHERE exam_subject_id=?', [$es['id']]) as $m) {
                    $marks[$m['student_id']] = $m;
                }
            }
        }
        $subjects = $classId ? DB::pairs('SELECT sb.id, sb.name FROM exam_subjects es JOIN subjects sb ON sb.id=es.subject_id WHERE es.exam_id=? AND es.class_id=? ORDER BY sb.name', [(int)$examId, $classId]) : [];
        view('exams/marks', compact('examId', 'classId', 'sectionId', 'subjectId', 'es', 'students', 'marks', 'subjects') + ['exams' => $this->exams(), 'title' => t('Marks Entry')]);
    }

    public function save_marks(): void
    {
        authorize('exams.marks');
        $es = DB::row('SELECT es.*, e.is_published FROM exam_subjects es JOIN exams e ON e.id=es.exam_id WHERE es.id=?', [input_int('exam_subject_id', 0)]);
        if (!$es) {
            abort(404);
        }
        $sectionId = input_int('section_id');
        if (Auth::scoped() && (!$sectionId || !Auth::canSubject($sectionId, (int)$es['subject_id']))) {
            abort(403);
        }
        if ($es['is_published'] && !can('exams.results')) {
            throw new ValidationException(['marks' => 'Results are published. Marks can no longer be changed.']);
        }
        $valid = array_map('intval', DB::col("SELECT id FROM students WHERE status='active' AND class_id=?", [$es['class_id']]));
        $n = 0;
        DB::tx(function () use ($es, $valid, &$n) {
            foreach ((array)($_POST['marks'] ?? []) as $sid => $val) {
                $sid = (int)$sid;
                if (!in_array($sid, $valid, true)) {
                    continue;
                }
                $absent = !empty($_POST['absent'][$sid]);
                $val = trim((string)$val);
                if ($val === '' && !$absent) {
                    DB::delete('marks', 'exam_subject_id=? AND student_id=?', [$es['id'], $sid]);
                    continue;
                }
                if (!$absent && (!is_numeric($val) || (float)$val < 0 || (float)$val > (float)$es['total_marks'])) {
                    throw new ValidationException(['marks' => "Invalid marks '$val'. Allowed range: 0 – " . (float)$es['total_marks']]);
                }
                DB::upsert('marks', ['exam_subject_id' => $es['id'], 'student_id' => $sid, 'obtained_marks' => $absent ? null : round((float)$val, 2), 'is_absent' => $absent ? 1 : 0,
                    'remarks' => mb_substr(trim((string)($_POST['remarks'][$sid] ?? '')), 0, 100) ?: null, 'entered_by' => Auth::id()], ['obtained_marks', 'is_absent', 'remarks', 'entered_by']);
                $n++;
            }
        });
        audit('marks', 'exams', (string)$es['exam_id'], "Saved $n marks for exam_subject #{$es['id']}");
        flash('success', sprintf(t('Marks saved for %d students.'), $n));
        redirect(url('exams/marks', ['exam_id' => $es['exam_id'], 'class_id' => $es['class_id'], 'section_id' => $sectionId, 'subject_id' => $es['subject_id']]));
    }

    /* ------------------------------------------------- results */
    public function results(): void
    {
        authorize('exams.view');
        $examId = input_int('exam_id');
        $classId = input_int('class_id');
        $sectionId = input_int('section_id');
        $exam = $examId ? $this->exam($examId) : null;
        $rows = [];
        $progress = [];
        if ($exam && $classId) {
            $p = [$examId, $classId];
            $sql = 'SELECT r.*, s.first_name, s.last_name, s.reg_no, s.roll_no, sec.name AS section_name FROM results r JOIN students s ON s.id=r.student_id LEFT JOIN sections sec ON sec.id=r.section_id WHERE r.exam_id=? AND r.class_id=?';
            if ($sectionId) {
                $sql .= ' AND r.section_id=?';
                $p[] = $sectionId;
            } elseif (Auth::scoped()) {
                $ids = Auth::teacherSectionIds();
                $sql .= ' AND r.section_id IN ' . DB::in($ids);
                $p = array_merge($p, $ids);
            }
            $rows = DB::all($sql . ' ORDER BY r.position IS NULL, r.position, r.percentage DESC', $p);
            $progress = DB::all("SELECT sb.name, es.total_marks, (SELECT COUNT(*) FROM marks m WHERE m.exam_subject_id=es.id) AS entered,
                (SELECT COUNT(*) FROM students s WHERE s.class_id=es.class_id AND s.status='active') AS strength FROM exam_subjects es JOIN subjects sb ON sb.id=es.subject_id WHERE es.exam_id=? AND es.class_id=? ORDER BY sb.name", [$examId, $classId]);
        }
        view('exams/results', compact('exam', 'examId', 'classId', 'sectionId', 'rows', 'progress') + ['exams' => $this->exams(), 'title' => t('Results')]);
    }

    public function generate(): void
    {
        authorize('exams.results');
        $examId = input_int('exam_id', 0);
        $classId = input_int('class_id', 0);
        $this->exam($examId);
        $n = ResultService::generate($examId, $classId);
        flash('success', sprintf(t('Results generated for %d students.'), $n));
        redirect(url('exams/results', ['exam_id' => $examId, 'class_id' => $classId]));
    }

    public function publish(): void
    {
        authorize('exams.results');
        $exam = $this->exam(input_int('exam_id', 0));
        $publish = input('publish') === '1';
        DB::update('exams', ['is_published' => $publish ? 1 : 0], 'id=?', [$exam['id']]);
        if ($publish && setting('notify_results', '1') === '1') {
            $uids = DB::col('SELECT s.user_id FROM results r JOIN students s ON s.id=r.student_id WHERE r.exam_id=? AND s.user_id IS NOT NULL
                UNION SELECT p.user_id FROM results r JOIN students s ON s.id=r.student_id JOIN parents p ON p.id=s.parent_id WHERE r.exam_id=? AND p.user_id IS NOT NULL', [$exam['id'], $exam['id']]);
            notify(array_map('intval', $uids), 'Result announced: ' . $exam['name'], 'The result of ' . $exam['name'] . ' is now available in the portal.', 'index.php?r=portal/results', 'exam');
        }
        audit($publish ? 'publish' : 'unpublish', 'exams', (string)$exam['id']);
        json_out(['ok' => true, 'message' => $publish ? t('Results published and parents notified.') : t('Results unpublished.')]);
    }

    /** Class result / tabulation sheet with subject-wise marks */
    public function result_sheet(): void
    {
        authorize('exams.view');
        $exam = $this->exam(input_int('exam_id', 0));
        $classId = input_int('class_id', 0);
        $sectionId = input_int('section_id');
        $subjects = DB::all('SELECT es.*, sb.name FROM exam_subjects es JOIN subjects sb ON sb.id=es.subject_id WHERE es.exam_id=? AND es.class_id=? ORDER BY sb.name', [$exam['id'], $classId]);
        $p = [$exam['id'], $classId];
        $sql = 'SELECT r.*, s.first_name, s.last_name, s.reg_no, s.roll_no FROM results r JOIN students s ON s.id=r.student_id WHERE r.exam_id=? AND r.class_id=?';
        if ($sectionId) {
            $sql .= ' AND r.section_id=?';
            $p[] = $sectionId;
        }
        $rows = DB::all($sql . ' ORDER BY s.roll_no+0, s.first_name', $p);
        $marks = [];
        $esIds = array_column($subjects, 'id');
        if ($esIds) {
            foreach (DB::all('SELECT * FROM marks WHERE exam_subject_id IN ' . DB::in($esIds), $esIds) as $m) {
                $marks[$m['student_id']][$m['exam_subject_id']] = $m;
            }
        }
        if (input('export')) {
            $head = array_merge(['Roll', 'Reg No', 'Name'], array_column($subjects, 'name'), ['Total', 'Obtained', '%', 'Grade', 'Position', 'Result']);
            $out = [];
            foreach ($rows as $r) {
                $line = [$r['roll_no'], $r['reg_no'], student_name($r)];
                foreach ($subjects as $sb) {
                    $m = $marks[$r['student_id']][$sb['id']] ?? null;
                    $line[] = !$m ? '' : ($m['is_absent'] ? 'Abs' : (float)$m['obtained_marks']);
                }
                $out[] = array_merge($line, [(float)$r['total_marks'], (float)$r['obtained_marks'], $r['percentage'], $r['grade'], $r['position'], $r['result_status']]);
            }
            export_table('result_sheet', $head, $out, input('export') === 'xls' ? 'xls' : 'csv');
        }
        $className = DB::val('SELECT name FROM classes WHERE id=?', [$classId]) . ($sectionId ? ' - ' . DB::val('SELECT name FROM sections WHERE id=?', [$sectionId]) : '');
        view('exams/result_sheet', ['exam' => $exam, 'subjects' => $subjects, 'rows' => $rows, 'marks' => $marks, 'landscape' => true,
            'title' => t('Result Sheet') . ' — ' . $exam['name'], 'subtitle' => $className . ' · ' . t('Session') . ' ' . $exam['session_name']], 'print');
    }

    /** Report card(s): single student or whole class */
    public function report_card(): void
    {
        $exam = $this->exam(input_int('exam_id', 0));
        $studentIds = [];
        if ($sid = input_int('student_id')) {
            $studentIds = [$sid];
        } elseif ($classId = input_int('class_id')) {
            authorize('exams.view');
            $p = [$exam['id'], $classId];
            $sql = 'SELECT r.student_id FROM results r JOIN students s ON s.id=r.student_id WHERE r.exam_id=? AND r.class_id=?';
            if ($sec = input_int('section_id')) {
                $sql .= ' AND r.section_id=?';
                $p[] = $sec;
            }
            $studentIds = array_map('intval', DB::col($sql . ' ORDER BY s.roll_no+0', $p));
        }
        if (Auth::isPortal()) {
            if (!$exam['is_published'] || array_diff($studentIds, PortalController::studentIds())) {
                abort(403);
            }
        } elseif (!can('exams.view')) {
            abort(403);
        }
        $cards = [];
        foreach ($studentIds as $sid) {
            $res = DB::row('SELECT r.*, s.first_name, s.last_name, s.reg_no, s.roll_no, s.photo, s.dob, c.name AS class_name, sec.name AS section_name, p.father_name
                FROM results r JOIN students s ON s.id=r.student_id JOIN classes c ON c.id=r.class_id LEFT JOIN sections sec ON sec.id=r.section_id LEFT JOIN parents p ON p.id=s.parent_id
                WHERE r.exam_id=? AND r.student_id=?', [$exam['id'], $sid]);
            if (!$res) {
                continue;
            }
            $subjects = DB::all('SELECT es.*, sb.name, m.obtained_marks, m.is_absent, m.remarks FROM exam_subjects es JOIN subjects sb ON sb.id=es.subject_id
                LEFT JOIN marks m ON m.exam_subject_id=es.id AND m.student_id=? WHERE es.exam_id=? AND es.class_id=? ORDER BY sb.name', [$sid, $exam['id'], $res['class_id']]);
            $sess = DB::row('SELECT start_date, end_date FROM academic_sessions WHERE id=?', [$exam['session_id']]);
            $att = DB::pairs('SELECT status, COUNT(*) FROM student_attendance WHERE student_id=? AND att_date BETWEEN ? AND ? GROUP BY status', [$sid, $sess['start_date'], min($sess['end_date'], $exam['end_date'] ?: $sess['end_date'])]);
            $strength = (int)DB::val('SELECT COUNT(*) FROM results WHERE exam_id=? AND class_id=?', [$exam['id'], $res['class_id']]);
            $cards[] = compact('res', 'subjects', 'att', 'strength');
        }
        if (!$cards) {
            abort(404, t('No result found. Generate results first.'));
        }
        view('exams/report_card', ['exam' => $exam, 'cards' => $cards, 'title' => t('Report Card'), 'noHeader' => true], 'print');
    }
}
