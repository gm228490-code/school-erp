<?php
declare(strict_types=1);

/**
 * Parent & Student portal. Every query is restricted to the logged-in user's own children / own record.
 */
final class PortalController
{
    /** Student ids the current portal user may see */
    public static function studentIds(): array
    {
        static $ids = null;
        if ($ids === null) {
            $uid = Auth::id();
            $ids = match (Auth::role()) {
                'student' => array_map('intval', DB::col('SELECT id FROM students WHERE user_id=?', [$uid])),
                'parent' => array_map('intval', DB::col('SELECT s.id FROM students s JOIN parents p ON p.id=s.parent_id WHERE p.user_id=? ORDER BY s.first_name', [$uid])),
                default => [],
            };
        }
        return $ids;
    }

    private function guard(): void
    {
        if (!Auth::isPortal()) {
            redirect(url('dashboard'));
        }
    }

    /** Currently selected child (validated) */
    private function student(): array
    {
        $this->guard();
        $ids = self::studentIds();
        if (!$ids) {
            view('portal/none', ['title' => t('My Portal')], 'portal');
        }
        $sel = input_int('student_id') ?? (int)($_SESSION['portal_student'] ?? 0);
        if (!in_array($sel, $ids, true)) {
            $sel = $ids[0];
        }
        $_SESSION['portal_student'] = $sel;
        return DB::row("SELECT s.*, c.name AS class_name, sec.name AS section_name, tr.name AS route_name, p.father_name, p.father_phone, p.mother_name
            FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN transport_routes tr ON tr.id=s.route_id LEFT JOIN parents p ON p.id=s.parent_id WHERE s.id=?", [$sel]);
    }

    private function children(): array
    {
        $ids = self::studentIds();
        return $ids ? DB::all("SELECT s.id, s.first_name, s.last_name, s.photo, c.name AS class_name FROM students s JOIN classes c ON c.id=s.class_id WHERE s.id IN " . DB::in($ids), $ids) : [];
    }

    private function render(string $view, array $data): never
    {
        view('portal/' . $view, $data + ['children' => $this->children()], 'portal');
    }

    public function index(): void
    {
        $s = $this->student();
        $sid = (int)$s['id'];
        $sess = current_session();
        $att = DB::pairs('SELECT status, COUNT(*) FROM student_attendance WHERE student_id=? AND att_date BETWEEN ? AND ? GROUP BY status', [$sid, $sess['start_date'] ?? '1970-01-01', $sess['end_date'] ?? '2999-12-31']);
        $due = (float)DB::val("SELECT COALESCE(SUM(net_amount-paid_amount),0) FROM fee_invoices WHERE student_id=? AND status IN ('unpaid','partial')", [$sid]);
        $lastResult = DB::row('SELECT r.*, e.name AS exam_name FROM results r JOIN exams e ON e.id=r.exam_id WHERE r.student_id=? AND e.is_published=1 ORDER BY e.start_date DESC, e.id DESC LIMIT 1', [$sid]);
        $homework = DB::all('SELECT h.*, sb.name AS subject, (SELECT status FROM homework_submissions hs WHERE hs.homework_id=h.id AND hs.student_id=?) AS sub_status FROM homework h JOIN subjects sb ON sb.id=h.subject_id
            WHERE h.class_id=? AND (h.section_id IS NULL OR h.section_id=?) AND h.due_date>=? ORDER BY h.due_date LIMIT 5', [$sid, $s['class_id'], $s['section_id'], date('Y-m-d')]);
        $today = DB::all('SELECT p.name AS period, p.start_time, p.end_time, p.is_break, sb.name AS subject, t.name AS teacher FROM periods p LEFT JOIN timetables tt ON tt.period_id=p.id AND tt.section_id=? AND tt.day_of_week=? AND tt.session_id=?
            LEFT JOIN subjects sb ON sb.id=tt.subject_id LEFT JOIN teachers t ON t.id=tt.teacher_id ORDER BY p.sort_order', [(int)$s['section_id'], (int)date('N'), session_id_current()]);
        $this->render('index', compact('s', 'att', 'due', 'lastResult', 'homework', 'today') + ['notices' => $this->noticeList($s, 5), 'title' => t('My Portal')]);
    }

    public function profile(): void
    {
        $s = $this->student();
        $this->render('profile', ['s' => $s, 'title' => t('Profile')]);
    }

    public function attendance(): void
    {
        $s = $this->student();
        $month = valid_month((string)input('month')) ? (string)input('month') : date('Y-m');
        $rows = DB::pairs("SELECT DAY(att_date), status FROM student_attendance WHERE student_id=? AND DATE_FORMAT(att_date,'%Y-%m')=?", [$s['id'], $month]);
        $summary = DB::all("SELECT DATE_FORMAT(att_date,'%Y-%m') AS m, SUM(status='present') p, SUM(status='absent') a, SUM(status='leave') l, SUM(status='late') lt FROM student_attendance WHERE student_id=? GROUP BY m ORDER BY m DESC LIMIT 12", [$s['id']]);
        $this->render('attendance', compact('s', 'month', 'rows', 'summary') + ['title' => t('Attendance')]);
    }

    public function results(): void
    {
        $s = $this->student();
        $results = DB::all('SELECT r.*, e.name AS exam_name, e.exam_type, ses.name AS session_name FROM results r JOIN exams e ON e.id=r.exam_id JOIN academic_sessions ses ON ses.id=e.session_id
            WHERE r.student_id=? AND e.is_published=1 ORDER BY e.start_date DESC, e.id DESC', [$s['id']]);
        $details = [];
        foreach ($results as $r) {
            $details[$r['exam_id']] = DB::all('SELECT sb.name, es.total_marks, es.pass_marks, m.obtained_marks, m.is_absent FROM exam_subjects es JOIN subjects sb ON sb.id=es.subject_id
                LEFT JOIN marks m ON m.exam_subject_id=es.id AND m.student_id=? WHERE es.exam_id=? AND es.class_id=? ORDER BY sb.name', [$s['id'], $r['exam_id'], $r['class_id']]);
        }
        $upcoming = DB::all('SELECT e.name, es.exam_date, es.start_time, sb.name AS subject FROM exam_subjects es JOIN exams e ON e.id=es.exam_id JOIN subjects sb ON sb.id=es.subject_id WHERE es.class_id=? AND es.exam_date>=? ORDER BY es.exam_date LIMIT 20', [$s['class_id'], date('Y-m-d')]);
        $this->render('results', compact('s', 'results', 'details', 'upcoming') + ['title' => t('Results')]);
    }

    public function fees(): void
    {
        $s = $this->student();
        $invoices = DB::all("SELECT * FROM fee_invoices WHERE student_id=? AND status<>'cancelled' ORDER BY issue_date DESC, id DESC", [$s['id']]);
        $payments = DB::all('SELECT p.*, i.invoice_no, i.title FROM fee_payments p JOIN fee_invoices i ON i.id=p.invoice_id WHERE p.student_id=? ORDER BY p.paid_on DESC, p.id DESC', [$s['id']]);
        $this->render('fees', compact('s', 'invoices', 'payments') + ['title' => t('Fees')]);
    }

    public function challan(): void
    {
        $s = $this->student();
        $inv = DB::row("SELECT i.*, st.first_name, st.last_name, st.reg_no, c.name AS class_name, sec.name AS section_name, p.father_name FROM fee_invoices i JOIN students st ON st.id=i.student_id
            LEFT JOIN classes c ON c.id=i.class_id LEFT JOIN sections sec ON sec.id=st.section_id LEFT JOIN parents p ON p.id=st.parent_id WHERE i.id=? AND i.student_id IN " . DB::in(self::studentIds()), array_merge([input_int('id', 0)], self::studentIds()));
        if (!$inv) {
            abort(404);
        }
        view('fees/challan', ['inv' => $inv, 'items' => DB::all('SELECT * FROM fee_invoice_items WHERE invoice_id=?', [$inv['id']]), 'title' => t('Fee Challan'), 'noHeader' => true], 'print');
    }

    public function homework(): void
    {
        $s = $this->student();
        $rows = DB::all('SELECT h.*, sb.name AS subject, t.name AS teacher, hs.id AS sub_id, hs.status AS sub_status, hs.submitted_at, hs.marks, hs.feedback, hs.content, hs.attachment AS sub_file
            FROM homework h JOIN subjects sb ON sb.id=h.subject_id LEFT JOIN teachers t ON t.id=h.teacher_id LEFT JOIN homework_submissions hs ON hs.homework_id=h.id AND hs.student_id=?
            WHERE h.class_id=? AND (h.section_id IS NULL OR h.section_id=?) AND h.session_id=? ORDER BY h.due_date DESC LIMIT 100', [$s['id'], $s['class_id'], $s['section_id'], session_id_current()]);
        $this->render('homework', ['s' => $s, 'rows' => $rows, 'title' => t('Homework')]);
    }

    /** Student (or parent on behalf) submits homework */
    public function submit(): void
    {
        $s = $this->student();
        $h = DB::row('SELECT * FROM homework WHERE id=? AND class_id=? AND (section_id IS NULL OR section_id=?)', [input_int('homework_id', 0), $s['class_id'], $s['section_id']]);
        if (!$h) {
            abort(404);
        }
        $existing = DB::row('SELECT * FROM homework_submissions WHERE homework_id=? AND student_id=?', [$h['id'], $s['id']]);
        if ($existing && $existing['status'] === 'reviewed') {
            throw new ValidationException(['content' => 'This homework has already been reviewed by the teacher.']);
        }
        $content = trim((string)input('content', ''));
        validate_or_fail(['content' => $content], ['content' => 'max:5000']);
        $file = upload_file('attachment', 'submissions', 'doc');
        if ($content === '' && !$file && !$existing) {
            throw new ValidationException(['content' => 'Write an answer or attach a file.']);
        }
        $data = ['content' => $content ?: ($existing['content'] ?? null), 'submitted_at' => date('Y-m-d H:i:s'), 'status' => date('Y-m-d') > $h['due_date'] ? 'late' : 'submitted'];
        if ($file) {
            $data['attachment'] = $file;
            if ($existing) {
                delete_upload($existing['attachment']);
            }
        }
        if ($existing) {
            DB::update('homework_submissions', $data, 'id=?', [$existing['id']]);
        } else {
            DB::insert('homework_submissions', $data + ['homework_id' => $h['id'], 'student_id' => $s['id']]);
        }
        if ($h['teacher_id'] && ($tu = DB::val('SELECT user_id FROM teachers WHERE id=?', [$h['teacher_id']]))) {
            notify([(int)$tu], 'Homework submitted', student_name($s) . ' submitted "' . $h['title'] . '"', 'index.php?r=homework/submissions&id=' . $h['id'], 'homework');
        }
        audit('submit', 'homework', (string)$h['id'], 'Student #' . $s['id']);
        flash('success', t('Homework submitted successfully.'));
        redirect(url('portal/homework'));
    }

    public function timetable(): void
    {
        $s = $this->student();
        $grid = [];
        foreach (DB::all('SELECT tt.*, sb.name AS subject, t.name AS teacher FROM timetables tt LEFT JOIN subjects sb ON sb.id=tt.subject_id LEFT JOIN teachers t ON t.id=tt.teacher_id WHERE tt.section_id=? AND tt.session_id=?', [(int)$s['section_id'], session_id_current()]) as $r) {
            $grid[$r['day_of_week']][$r['period_id']] = $r;
        }
        $this->render('timetable', ['s' => $s, 'grid' => $grid, 'periods' => DB::all('SELECT * FROM periods ORDER BY sort_order'), 'days' => array_intersect_key(days_of_week(), array_flip(school_days())), 'title' => t('Timetable')]);
    }

    private function noticeList(array $s, int $limit = 50): array
    {
        $aud = Auth::role() === 'parent' ? 'parents' : 'students';
        return DB::all("SELECT * FROM notices WHERE publish_date<=? AND (expiry_date IS NULL OR expiry_date>=?) AND (audience IN ('all', ?) OR (audience='class' AND class_id=?)) ORDER BY publish_date DESC, id DESC LIMIT $limit",
            [date('Y-m-d'), date('Y-m-d'), $aud, $s['class_id']]);
    }

    public function notices(): void
    {
        $s = $this->student();
        $this->render('notices', ['s' => $s, 'notices' => $this->noticeList($s), 'title' => t('Announcements')]);
    }
}
