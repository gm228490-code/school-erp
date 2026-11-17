<?php
declare(strict_types=1);

final class StudentsController
{
    private const STUDENT_FIELDS = ['first_name', 'last_name', 'gender', 'dob', 'b_form', 'religion', 'nationality', 'blood_group', 'phone', 'email', 'address', 'city',
        'previous_school', 'previous_class', 'admission_date', 'class_id', 'section_id', 'roll_no', 'route_id'];
    private const PARENT_FIELDS = ['father_name', 'father_cnic', 'father_phone', 'father_occupation', 'mother_name', 'mother_cnic', 'mother_phone', 'mother_occupation',
        'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_cnic', 'parent_email', 'parent_address'];

    /* ------------------------------------------------------------ list */
    private function listQuery(): array
    {
        $w = ['1=1'];
        $p = [];
        if ($v = input_int('class_id')) { $w[] = 's.class_id=?'; $p[] = $v; }
        if ($v = input_int('section_id')) { $w[] = 's.section_id=?'; $p[] = $v; }
        $status = (string)input('status', 'active');
        if ($status !== 'all' && in_array($status, ['active', 'withdrawn', 'transferred', 'graduated', 'suspended'], true)) { $w[] = 's.status=?'; $p[] = $status; }
        if (in_array($g = (string)input('gender', ''), ['male', 'female', 'other'], true)) { $w[] = 's.gender=?'; $p[] = $g; }
        if ($v = input_int('route_id')) { $w[] = 's.route_id=?'; $p[] = $v; }
        if (($q = trim((string)input('q', ''))) !== '') {
            $w[] = "(s.reg_no LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) LIKE ? OR s.b_form LIKE ? OR p.father_name LIKE ? OR p.father_phone LIKE ? OR p.father_cnic LIKE ? OR s.roll_no=?)";
            array_push($p, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", $q);
        }
        if (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $w[] = 's.section_id IN ' . DB::in($ids);
            $p = array_merge($p, $ids);
        }
        $from = 'students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id LEFT JOIN transport_routes tr ON tr.id=s.route_id';
        $sel = "s.*, c.name AS class_name, sec.name AS section_name, p.father_name, p.father_phone, p.father_cnic, tr.name AS route_name";
        return [$from, implode(' AND ', $w), $p, $sel];
    }

    public function index(): void
    {
        authorize('students.view');
        [$from, $where, $params, $sel] = $this->listQuery();
        $order = order_by(['reg_no' => 's.reg_no', 'name' => 's.first_name', 'class' => 'c.numeric_order, sec.name, s.roll_no+0', 'admission' => 's.admission_date', 'father' => 'p.father_name', 'roll' => 's.roll_no+0'], 'c.numeric_order, sec.name, s.roll_no+0, s.first_name');
        $p = paginate("SELECT COUNT(*) FROM $from WHERE $where", "SELECT $sel FROM $from WHERE $where ORDER BY $order", $params, 25);
        view('students/index', ['p' => $p, 'title' => t('Students')]);
    }

    public function export(): void
    {
        authorize('students.import', 'reports.view');
        [$from, $where, $params, $sel] = $this->listQuery();
        $rows = DB::all("SELECT $sel FROM $from WHERE $where ORDER BY c.numeric_order, sec.name, s.roll_no+0", $params);
        $out = array_map(fn($r) => [$r['reg_no'], $r['roll_no'], $r['first_name'], $r['last_name'], $r['gender'], $r['dob'], $r['b_form'], $r['class_name'], $r['section_name'],
            $r['father_name'], $r['father_cnic'], $r['father_phone'], $r['phone'], $r['address'], $r['city'], $r['admission_date'], $r['status']], $rows);
        audit('export', 'students', null, count($out) . ' rows');
        export_table('students', ['Reg No', 'Roll No', 'First Name', 'Last Name', 'Gender', 'DOB', 'B-Form', 'Class', 'Section', 'Father Name', 'Father CNIC', 'Father Phone', 'Phone', 'Address', 'City', 'Admission Date', 'Status'], $out, input('format') === 'xls' ? 'xls' : 'csv');
    }

    /* ------------------------------------------------------------ admission */
    public function create(): void
    {
        authorize('students.create');
        view('students/form', ['s' => null, 'par' => null, 'title' => t('New Admission'), 'errors' => form_errors()]);
    }

    private function collect(?int $id): array
    {
        $in = [];
        foreach (array_merge(self::STUDENT_FIELDS, self::PARENT_FIELDS) as $f) {
            $in[$f] = is_string($_POST[$f] ?? null) ? trim($_POST[$f]) : null;
        }
        $in['parent_id'] = input_int('parent_id');
        validate_or_fail($in, [
            'first_name' => 'required|max:60', 'last_name' => 'max:60', 'gender' => 'required|in:male,female,other', 'dob' => 'date',
            'b_form' => 'cnic|unique:students,b_form' . ($id ? ",$id" : ''), 'religion' => 'max:30', 'nationality' => 'max:40', 'blood_group' => 'in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'phone' => 'phone', 'email' => 'email', 'city' => 'max:60', 'previous_school' => 'max:150', 'previous_class' => 'max:50',
            'admission_date' => 'required|date', 'class_id' => 'required|exists:classes,id', 'section_id' => 'exists:sections,id', 'roll_no' => 'max:20', 'route_id' => 'exists:transport_routes,id',
            'father_cnic' => 'cnic', 'father_phone' => 'phone', 'mother_cnic' => 'cnic', 'mother_phone' => 'phone', 'guardian_phone' => 'phone', 'guardian_cnic' => 'cnic', 'parent_email' => 'email',
        ], ['b_form' => 'B-Form / CNIC', 'dob' => 'Date of birth']);
        if (!$in['parent_id']) {
            validate_or_fail($in, ['father_name' => 'required|max:120'], ['father_name' => "Father's / guardian's name"]);
        }
        if ($in['dob'] && $in['dob'] >= date('Y-m-d')) {
            throw new ValidationException(['dob' => 'Date of birth must be in the past.']);
        }
        if ($in['section_id'] && (int)DB::val('SELECT class_id FROM sections WHERE id=?', [$in['section_id']]) !== (int)$in['class_id']) {
            throw new ValidationException(['section_id' => 'Section does not belong to the selected class.']);
        }
        $in = nullify($in);
        foreach (['class_id', 'section_id', 'route_id', 'parent_id'] as $k) {
            $in[$k] = $in[$k] === null ? null : (int)$in[$k];
        }
        return $in;
    }

    private function saveParent(array $in, ?int $existingParentId): int
    {
        $pdata = nullify([
            'father_name' => $in['father_name'], 'father_cnic' => $in['father_cnic'], 'father_phone' => $in['father_phone'], 'father_occupation' => $in['father_occupation'],
            'mother_name' => $in['mother_name'], 'mother_cnic' => $in['mother_cnic'], 'mother_phone' => $in['mother_phone'], 'mother_occupation' => $in['mother_occupation'],
            'guardian_name' => $in['guardian_name'], 'guardian_relation' => $in['guardian_relation'], 'guardian_phone' => $in['guardian_phone'], 'guardian_cnic' => $in['guardian_cnic'],
            'email' => $in['parent_email'], 'address' => $in['parent_address'] ?? $in['address'],
        ]);
        if ($existingParentId) {
            if ($pdata['father_name']) {
                if ($pdata['father_cnic'] && DB::val('SELECT id FROM parents WHERE father_cnic=? AND id<>?', [$pdata['father_cnic'], $existingParentId])) {
                    throw new ValidationException(['father_cnic' => 'Another parent record already uses this CNIC.']);
                }
                DB::update('parents', $pdata, 'id=?', [$existingParentId]);
            }
            return $existingParentId;
        }
        // Reuse an existing parent with the same CNIC (siblings) - prevents duplicates
        if ($pdata['father_cnic'] && ($pid = DB::val('SELECT id FROM parents WHERE father_cnic=?', [$pdata['father_cnic']]))) {
            DB::update('parents', array_filter($pdata, fn($v) => $v !== null), 'id=?', [$pid]);
            return (int)$pid;
        }
        return DB::insert('parents', $pdata);
    }

    private function checkCapacity(?int $sectionId, ?int $exceptStudent = null): void
    {
        if (!$sectionId) {
            return;
        }
        $cap = (int)DB::val('SELECT capacity FROM sections WHERE id=?', [$sectionId]);
        $count = (int)DB::val("SELECT COUNT(*) FROM students WHERE section_id=? AND status='active' AND id<>?", [$sectionId, (int)$exceptStudent]);
        if ($cap && $count >= $cap && !input('override_capacity')) {
            throw new ValidationException(['section_id' => "This section is full ($count / $cap). Tick 'Allow over capacity' to proceed."]);
        }
    }

    public function store(): void
    {
        authorize('students.create');
        $in = $this->collect(null);
        // Duplicate prevention: same name + father + DOB
        $dup = DB::val("SELECT s.reg_no FROM students s LEFT JOIN parents p ON p.id=s.parent_id WHERE s.first_name=? AND IFNULL(s.last_name,'')=? AND IFNULL(s.dob,'')=? AND (p.father_name=? OR s.parent_id=?)",
            [$in['first_name'], (string)$in['last_name'], (string)$in['dob'], (string)$in['father_name'], (int)$in['parent_id']]);
        if ($dup && !input('allow_duplicate')) {
            throw new ValidationException(['first_name' => "A student with the same name, father and date of birth already exists ($dup)."]);
        }
        $this->checkCapacity($in['section_id']);
        $photo = upload_file('photo', 'students', 'image');
        $creds = [];
        $id = DB::tx(function () use ($in, $photo, &$creds) {
            $parentId = $this->saveParent($in, $in['parent_id']);
            $sid = session_id_current();
            $roll = $in['roll_no'] ?: ($in['section_id'] ? (string)((int)DB::val("SELECT COALESCE(MAX(roll_no+0),0) FROM students WHERE section_id=? AND status='active'", [$in['section_id']]) + 1) : null);
            $data = array_intersect_key($in, array_flip(self::STUDENT_FIELDS));
            $data['roll_no'] = $roll;
            $data += ['reg_no' => next_number('student'), 'parent_id' => $parentId, 'session_id' => $sid, 'photo' => $photo, 'status' => 'active'];
            $id = DB::insert('students', $data);
            DB::insert('admissions', ['student_id' => $id, 'session_id' => $sid, 'class_id' => $in['class_id'], 'section_id' => $in['section_id'], 'admission_date' => $in['admission_date'], 'created_by' => Auth::id()]);
            $this->history($id, 'admission', 'Admitted in ' . DB::val('SELECT name FROM classes WHERE id=?', [$in['class_id']]));
            // documents uploaded with the admission form
            $this->storeDocuments($id);
            // admission invoice
            if (input('admission_invoice') && can('fees.manage')) {
                $student = DB::row('SELECT * FROM students WHERE id=?', [$id]);
                $cats = array_map('intval', DB::col("SELECT id FROM fee_categories WHERE frequency IN ('one_time','annual') AND name<>'Other Charges'"));
                if (input('first_month')) {
                    $cats = array_merge($cats, array_map('intval', DB::col("SELECT id FROM fee_categories WHERE frequency='monthly'")));
                }
                $items = FeeService::buildItems($student, $sid, $cats);
                if ($items) {
                    FeeService::createInvoice($student, 'Admission Fee', input('first_month') ? date('Y-m') : null, $items, date('Y-m-d'), date('Y-m-d', strtotime('+7 days')));
                }
            }
            if (input('create_student_login')) {
                $creds['Student'] = AccountService::ensureLogin('students', $id, 'student', trim($in['first_name'] . ' ' . $in['last_name']), $data['reg_no'], $in['email'], $in['phone']);
            }
            if (input('create_parent_login')) {
                $par = DB::row('SELECT * FROM parents WHERE id=?', [$parentId]);
                $creds['Parent'] = AccountService::ensureLogin('parents', $parentId, 'parent', $par['father_name'], 'p' . preg_replace('/\D/', '', (string)($par['father_cnic'] ?: $par['father_phone'] ?: $data['reg_no'])), $par['email'], $par['father_phone']);
            }
            audit('create', 'students', (string)$id, 'Admission ' . $data['reg_no'] . ' ' . $in['first_name']);
            return $id;
        });
        $reg = DB::val('SELECT reg_no FROM students WHERE id=?', [$id]);
        flash('success', sprintf(t('Student admitted successfully. Registration No: %s'), $reg));
        foreach ($creds as $who => [$u, $pw]) {
            flash('info', t($who . ' portal login') . " — " . t('Username') . ": $u  " . t('Password') . ": $pw  (" . t('shown only once') . ')');
        }
        redirect(url('students/show', ['id' => $id]));
    }

    public function edit(): void
    {
        authorize('students.edit');
        $s = $this->find();
        view('students/form', ['s' => $s, 'par' => $s['parent_id'] ? DB::row('SELECT * FROM parents WHERE id=?', [$s['parent_id']]) : null, 'title' => t('Edit Student'), 'errors' => form_errors()]);
    }

    public function update(): void
    {
        authorize('students.edit');
        $s = $this->find();
        $in = $this->collect((int)$s['id']);
        if ($in['section_id'] && (int)$in['section_id'] !== (int)$s['section_id']) {
            $this->checkCapacity($in['section_id'], (int)$s['id']);
        }
        $photo = upload_file('photo', 'students', 'image');
        DB::tx(function () use ($in, $s, $photo) {
            $parentId = $this->saveParent($in, $in['parent_id'] ?: ($s['parent_id'] ? (int)$s['parent_id'] : null));
            $data = array_intersect_key($in, array_flip(self::STUDENT_FIELDS));
            $data['parent_id'] = $parentId;
            if ($photo) {
                $data['photo'] = $photo;
                delete_upload($s['photo']);
            }
            DB::update('students', $data, 'id=?', [$s['id']]);
            if ((int)$in['class_id'] !== (int)$s['class_id'] || (int)$in['section_id'] !== (int)$s['section_id']) {
                $this->history((int)$s['id'], 'class_change', 'Class/section changed from ' . $s['class_name'] . ' ' . $s['section_name'] . ' to ' . DB::val("SELECT CONCAT(c.name,' ',IFNULL(sec.name,'')) FROM classes c LEFT JOIN sections sec ON sec.id=? WHERE c.id=?", [$in['section_id'], $in['class_id']]));
            }
            $this->storeDocuments((int)$s['id']);
            audit('update', 'students', (string)$s['id'], 'Profile updated');
        });
        flash('success', t('Student record updated.'));
        redirect(url('students/show', ['id' => $s['id']]));
    }

    /* ------------------------------------------------------------ profile */
    private function find(?int $id = null): array
    {
        $id ??= input_int('id', 0);
        $s = DB::row("SELECT s.*, c.name AS class_name, sec.name AS section_name, ses.name AS session_name, tr.name AS route_name, tr.monthly_fee AS route_fee
            FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN academic_sessions ses ON ses.id=s.session_id
            LEFT JOIN transport_routes tr ON tr.id=s.route_id WHERE s.id=?", [$id]);
        if (!$s) {
            abort(404);
        }
        if (Auth::scoped() && !Auth::canSection($s['section_id'] ? (int)$s['section_id'] : null)) {
            abort(403);
        }
        return $s;
    }

    public function show(): void
    {
        authorize('students.view');
        $s = $this->find();
        $id = (int)$s['id'];
        $data = [
            's' => $s,
            'par' => $s['parent_id'] ? DB::row('SELECT p.*, u.username FROM parents p LEFT JOIN users u ON u.id=p.user_id WHERE p.id=?', [$s['parent_id']]) : null,
            'login' => $s['user_id'] ? DB::row('SELECT username, last_login, status FROM users WHERE id=?', [$s['user_id']]) : null,
            'siblings' => $s['parent_id'] ? DB::all("SELECT s.id, s.first_name, s.last_name, s.reg_no, c.name AS class_name FROM students s JOIN classes c ON c.id=s.class_id WHERE s.parent_id=? AND s.id<>?", [$s['parent_id'], $id]) : [],
            'att' => DB::pairs('SELECT status, COUNT(*) FROM student_attendance WHERE student_id=? AND session_id=? GROUP BY status', [$id, session_id_current()]),
            'att_recent' => DB::all('SELECT att_date, status, remarks FROM student_attendance WHERE student_id=? ORDER BY att_date DESC LIMIT 15', [$id]),
            'invoices' => can('fees.view') ? DB::all('SELECT * FROM fee_invoices WHERE student_id=? ORDER BY issue_date DESC, id DESC LIMIT 50', [$id]) : [],
            'payments' => can('fees.view') ? DB::all('SELECT p.*, i.invoice_no FROM fee_payments p JOIN fee_invoices i ON i.id=p.invoice_id WHERE p.student_id=? ORDER BY p.paid_on DESC, p.id DESC LIMIT 50', [$id]) : [],
            'discounts' => can('fees.view') ? DB::all('SELECT d.*, fc.name AS cat_name FROM student_discounts d LEFT JOIN fee_categories fc ON fc.id=d.fee_category_id WHERE d.student_id=?', [$id]) : [],
            'results' => DB::all('SELECT r.*, e.name AS exam_name, e.exam_type, ses.name AS session_name FROM results r JOIN exams e ON e.id=r.exam_id JOIN academic_sessions ses ON ses.id=e.session_id WHERE r.student_id=? ORDER BY e.start_date DESC, e.id DESC', [$id]),
            'docs' => DB::all('SELECT * FROM student_documents WHERE student_id=? ORDER BY id DESC', [$id]),
            'history' => DB::all('SELECT h.*, u.name AS by_name, c.name AS class_name, ses.name AS session_name FROM student_history h LEFT JOIN users u ON u.id=h.created_by LEFT JOIN classes c ON c.id=h.class_id LEFT JOIN academic_sessions ses ON ses.id=h.session_id WHERE h.student_id=? ORDER BY h.id DESC', [$id]),
            'books' => DB::all("SELECT bi.*, b.title FROM book_issues bi JOIN books b ON b.id=bi.book_id WHERE bi.member_type='student' AND bi.member_id=? ORDER BY bi.id DESC LIMIT 20", [$id]),
            'title' => student_name($s),
        ];
        view('students/show', $data);
    }

    public function print_profile(): void
    {
        authorize('students.view');
        $s = $this->find();
        view('students/print_profile', [
            's' => $s, 'par' => $s['parent_id'] ? DB::row('SELECT * FROM parents WHERE id=?', [$s['parent_id']]) : null,
            'att' => DB::pairs('SELECT status, COUNT(*) FROM student_attendance WHERE student_id=? AND session_id=? GROUP BY status', [$s['id'], session_id_current()]),
            'history' => DB::all('SELECT * FROM student_history WHERE student_id=? ORDER BY id', [$s['id']]),
            'title' => t('Student Profile'),
        ], 'print');
    }

    /* ------------------------------------------------------------ ID cards */
    public function idcard(): void
    {
        authorize('students.view');
        $s = $this->find();
        view('students/idcards', ['students' => [$s], 'title' => t('Student ID Card'), 'noHeader' => true], 'print');
    }

    public function idcards(): void
    {
        authorize('students.view');
        $classId = input_int('class_id');
        if (!$classId) {
            view('students/idcards_select', ['title' => t('Student ID Cards')]);
        }
        $p = [$classId];
        $sql = "SELECT s.*, c.name AS class_name, sec.name AS section_name, p.father_name, p.father_phone FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id WHERE s.status='active' AND s.class_id=?";
        if ($sec = input_int('section_id')) {
            $sql .= ' AND s.section_id=?';
            $p[] = $sec;
        }
        if (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $sql .= ' AND s.section_id IN ' . DB::in($ids);
            $p = array_merge($p, $ids);
        }
        view('students/idcards', ['students' => DB::all($sql . ' ORDER BY sec.name, s.roll_no+0, s.first_name', $p), 'title' => t('Student ID Cards'), 'noHeader' => true], 'print');
    }

    /* ------------------------------------------------------------ status / transfer */
    public function status_form(): void
    {
        authorize('students.promote');
        $s = $this->find();
        echo render('students/status_form', ['s' => $s], null);
    }

    public function change_status(): void
    {
        authorize('students.promote');
        $s = $this->find();
        $mode = (string)input('mode');
        if ($mode === 'transfer_section') {
            $classId = input_int('class_id');
            $sectionId = input_int('section_id');
            validate_or_fail(['class_id' => $classId, 'section_id' => $sectionId], ['class_id' => 'required|exists:classes,id', 'section_id' => 'exists:sections,id']);
            if ($sectionId && (int)DB::val('SELECT class_id FROM sections WHERE id=?', [$sectionId]) !== $classId) {
                throw new ValidationException(['section_id' => 'Section does not belong to the selected class.']);
            }
            $this->checkCapacity($sectionId, (int)$s['id']);
            DB::update('students', ['class_id' => $classId, 'section_id' => $sectionId], 'id=?', [$s['id']]);
            $to = DB::val("SELECT CONCAT(c.name,' ',IFNULL(sec.name,'')) FROM classes c LEFT JOIN sections sec ON sec.id=? WHERE c.id=?", [$sectionId, $classId]);
            $this->history((int)$s['id'], 'transfer', 'Transferred from ' . $s['class_name'] . ' ' . $s['section_name'] . ' to ' . $to . '. ' . input('reason', ''));
            audit('transfer', 'students', (string)$s['id'], "Transferred to $to");
            json_out(['ok' => true, 'message' => t('Student transferred successfully.')]);
        }
        $status = (string)input('status');
        $date = input_date('status_date', date('Y-m-d'));
        $reason = mb_substr((string)input('reason', ''), 0, 255);
        validate_or_fail(['status' => $status, 'reason' => $reason], ['status' => 'required|in:active,withdrawn,transferred,graduated,suspended', 'reason' => $status === 'active' ? '' : 'required|max:255']);
        DB::update('students', ['status' => $status, 'status_date' => $date, 'status_reason' => $reason ?: null], 'id=?', [$s['id']]);
        if ($s['user_id']) {
            DB::update('users', ['status' => in_array($status, ['active', 'suspended'], true) ? 'active' : 'inactive'], 'id=?', [$s['user_id']]);
        }
        $this->history((int)$s['id'], $status === 'active' ? 'reactivated' : $status, ucfirst($status) . ' on ' . fmt_date($date) . ($reason ? ": $reason" : ''));
        audit('status', 'students', (string)$s['id'], "Status changed to $status");
        json_out(['ok' => true, 'message' => t('Student status updated.')]);
    }

    public function leaving_certificate(): void
    {
        authorize('students.view');
        $s = $this->find();
        view('students/leaving_certificate', ['s' => $s, 'par' => $s['parent_id'] ? DB::row('SELECT * FROM parents WHERE id=?', [$s['parent_id']]) : null, 'title' => t('School Leaving Certificate')], 'print');
    }

    /* ------------------------------------------------------------ promotion & year closing */
    public function promote(): void
    {
        authorize('students.promote');
        $from = input_int('from_class');
        $fromSec = input_int('from_section');
        $examId = input_int('exam_id');
        $students = [];
        if ($from) {
            $p = [$from];
            $sql = "SELECT s.*, sec.name AS section_name" . ($examId ? ', r.result_status, r.percentage' : '') . " FROM students s LEFT JOIN sections sec ON sec.id=s.section_id"
                . ($examId ? ' LEFT JOIN results r ON r.student_id=s.id AND r.exam_id=' . (int)$examId : '') . " WHERE s.status='active' AND s.class_id=?";
            if ($fromSec) {
                $sql .= ' AND s.section_id=?';
                $p[] = $fromSec;
            }
            $students = DB::all($sql . ' ORDER BY sec.name, s.roll_no+0, s.first_name', $p);
        }
        $nextClass = $from ? DB::val('SELECT id FROM classes WHERE numeric_order > (SELECT numeric_order FROM classes WHERE id=?) ORDER BY numeric_order LIMIT 1', [$from]) : null;
        view('students/promote', [
            'students' => $students, 'from' => $from, 'fromSec' => $fromSec, 'examId' => $examId, 'nextClass' => $nextClass,
            'exams' => DB::pairs("SELECT id, name FROM exams WHERE session_id=? ORDER BY start_date DESC", [session_id_current()]),
            'title' => t('Student Promotion'),
        ]);
    }

    public function do_promote(): void
    {
        authorize('students.promote');
        $ids = array_map('intval', (array)($_POST['students'] ?? []));
        $action = (string)input('action', 'promote');
        $toClass = input_int('to_class');
        $toSection = input_int('to_section');
        $toSession = input_int('to_session') ?: session_id_current();
        if (!$ids) {
            throw new ValidationException(['students' => 'Select at least one student.']);
        }
        if ($action === 'promote') {
            validate_or_fail(['to_class' => $toClass, 'to_session' => $toSession], ['to_class' => 'required|exists:classes,id', 'to_session' => 'required|exists:academic_sessions,id'], ['to_class' => 'Target class']);
            if ($toSection && (int)DB::val('SELECT class_id FROM sections WHERE id=?', [$toSection]) !== $toClass) {
                throw new ValidationException(['to_section' => 'Section does not belong to the target class.']);
            }
        }
        $count = DB::tx(function () use ($ids, $action, $toClass, $toSection, $toSession) {
            $n = 0;
            $className = $toClass ? DB::val('SELECT name FROM classes WHERE id=?', [$toClass]) : '';
            foreach ($ids as $sid) {
                $s = DB::row("SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.status='active'", [$sid]);
                if (!$s) {
                    continue;
                }
                if ($action === 'graduate') {
                    DB::update('students', ['status' => 'graduated', 'status_date' => date('Y-m-d'), 'status_reason' => 'Passed out from ' . $s['class_name']], 'id=?', [$sid]);
                    $this->history($sid, 'graduated', 'Graduated / passed out from ' . $s['class_name']);
                } else {
                    DB::update('students', ['class_id' => $toClass, 'section_id' => $toSection, 'session_id' => $toSession], 'id=?', [$sid]);
                    DB::q('UPDATE student_history SET session_id=? WHERE id=?', [$toSession, $this->history($sid, $action === 'repeat' ? 'detained' : 'promotion', ($action === 'repeat' ? 'Detained in ' : 'Promoted from ' . $s['class_name'] . ' to ') . $className, $toClass, $toSection)]);
                }
                $n++;
            }
            return $n;
        });
        audit('promote', 'students', null, "$action: $count students");
        flash('success', sprintf(t('%d students processed successfully.'), $count));
        redirect(url('students/promote'));
    }

    /** Academic year closing: close current session and open the next one */
    public function close_year(): void
    {
        authorize('students.promote');
        if (!can('academics.manage')) {
            abort(403);
        }
        $cur = current_session();
        $name = trim((string)input('new_session_name'));
        $start = input_date('new_start');
        $end = input_date('new_end');
        validate_or_fail(['new_session_name' => $name, 'new_start' => $start, 'new_end' => $end], ['new_session_name' => 'required|max:30|unique:academic_sessions,name', 'new_start' => 'required|date', 'new_end' => 'required|date'], ['new_session_name' => 'New session name']);
        if ($end <= $start) {
            throw new ValidationException(['new_end' => 'End date must be after start date.']);
        }
        DB::tx(function () use ($cur, $name, $start, $end) {
            DB::q("UPDATE academic_sessions SET status='closed', is_current=0 WHERE id=?", [$cur['id']]);
            DB::q('UPDATE academic_sessions SET is_current=0');
            $new = DB::insert('academic_sessions', ['name' => $name, 'start_date' => $start, 'end_date' => $end, 'is_current' => 1, 'status' => 'active']);
            // carry the fee structure forward
            DB::q('INSERT IGNORE INTO fee_structures (session_id, class_id, fee_category_id, amount) SELECT ?, class_id, fee_category_id, amount FROM fee_structures WHERE session_id=?', [$new, $cur['id']]);
            unset($_SESSION['session_id']);
        });
        audit('close_year', 'sessions', (string)$cur['id'], "Closed {$cur['name']} and opened $name");
        flash('success', sprintf(t('Academic year %s closed. %s is now the current session. Fee structure copied.'), $cur['name'], $name));
        redirect(url('students/promote'));
    }

    /* ------------------------------------------------------------ documents */
    private function storeDocuments(int $studentId): void
    {
        $titles = (array)($_POST['doc_title'] ?? []);
        if (empty($_FILES['doc_file']['name']) || !is_array($_FILES['doc_file']['name'])) {
            return;
        }
        $files = $_FILES['doc_file'];
        foreach ($files['name'] as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $_FILES['_doc'] = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
            $path = upload_file('_doc', 'documents', 'doc');
            DB::insert('student_documents', ['student_id' => $studentId, 'title' => mb_substr(trim((string)($titles[$i] ?? '')) ?: pathinfo($name, PATHINFO_FILENAME), 0, 150), 'file_path' => $path, 'original_name' => mb_substr($name, 0, 255), 'uploaded_by' => Auth::id()]);
        }
    }

    public function upload_document(): void
    {
        authorize('students.edit');
        $s = $this->find();
        $title = trim((string)input('title'));
        validate_or_fail(['title' => $title], ['title' => 'required|max:150']);
        $path = upload_file('file', 'documents', 'doc', true);
        DB::insert('student_documents', ['student_id' => $s['id'], 'title' => $title, 'file_path' => $path, 'original_name' => mb_substr($_FILES['file']['name'] ?? '', 0, 255), 'uploaded_by' => Auth::id()]);
        audit('upload', 'student_documents', (string)$s['id'], $title);
        flash('success', t('Document uploaded.'));
        redirect(url('students/show', ['id' => $s['id']]) . '#docs');
    }

    public function delete_document(): void
    {
        authorize('students.edit');
        $d = DB::row('SELECT * FROM student_documents WHERE id=?', [input_int('id', 0)]);
        if (!$d) {
            abort(404);
        }
        DB::delete('student_documents', 'id=?', [$d['id']]);
        delete_upload($d['file_path']);
        audit('delete', 'student_documents', (string)$d['id'], $d['title']);
        json_out(['ok' => true, 'message' => t('Document deleted.')]);
    }

    /* ------------------------------------------------------------ logins */
    public function student_login(): void
    {
        authorize('students.edit');
        $s = $this->find();
        [$u, $pw] = AccountService::ensureLogin('students', (int)$s['id'], 'student', student_name($s), $s['reg_no'], $s['email'], $s['phone']);
        json_out(['ok' => true, 'sticky' => true, 'reload' => true, 'message' => t('Student login') . ' — ' . t('Username') . ": $u | " . t('Password') . ": $pw"]);
    }

    public function parent_login(): void
    {
        authorize('students.edit');
        $par = DB::row('SELECT * FROM parents WHERE id=?', [input_int('id', 0)]);
        if (!$par) {
            abort(404);
        }
        [$u, $pw] = AccountService::ensureLogin('parents', (int)$par['id'], 'parent', $par['father_name'], 'p' . preg_replace('/\D/', '', (string)($par['father_cnic'] ?: $par['father_phone'] ?: $par['id'])), $par['email'], $par['father_phone']);
        json_out(['ok' => true, 'sticky' => true, 'reload' => true, 'message' => t('Parent login') . ' — ' . t('Username') . ": $u | " . t('Password') . ": $pw"]);
    }

    /* ------------------------------------------------------------ delete */
    public function delete(): void
    {
        authorize('students.delete');
        $s = $this->find();
        if (DB::val('SELECT COUNT(*) FROM fee_payments WHERE student_id=?', [$s['id']])) {
            throw new HttpException(t('This student has fee payments on record. Withdraw the student instead of deleting.'), 400);
        }
        DB::tx(function () use ($s) {
            DB::delete('fee_invoices', 'student_id=?', [$s['id']]);
            DB::delete('students', 'id=?', [$s['id']]);
            if ($s['user_id']) {
                DB::delete('users', 'id=?', [$s['user_id']]);
            }
        });
        delete_upload($s['photo']);
        audit('delete', 'students', (string)$s['id'], $s['reg_no'] . ' ' . student_name($s));
        json_out(['ok' => true, 'message' => t('Student deleted.'), 'redirect' => url('students')]);
    }

    /* ------------------------------------------------------------ import */
    public function import(): void
    {
        authorize('students.import');
        if (input('template')) {
            export_table('student_import_template', ['first_name', 'last_name', 'gender', 'dob', 'b_form', 'class', 'section', 'roll_no', 'father_name', 'father_cnic', 'father_phone', 'mother_name', 'phone', 'address', 'city', 'admission_date'],
                [['Ali', 'Khan', 'male', '2015-03-21', '35202-1234567-1', 'Class 5', 'A', '1', 'Imran Khan', '35202-7654321-3', '0300-1234567', 'Sara Khan', '', 'House 1, Street 2', 'Lahore', date('Y-m-d')]]);
        }
        if (!is_post()) {
            view('students/import', ['title' => t('Import Students')]);
        }
        $f = $_FILES['csv'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new ValidationException(['csv' => 'Please choose a CSV file.']);
        }
        $fh = fopen($f['tmp_name'], 'r');
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fh);
        }
        $head = array_map(fn($h) => strtolower(trim((string)$h)), fgetcsv($fh) ?: []);
        if (!in_array('first_name', $head, true) || !in_array('class', $head, true)) {
            throw new ValidationException(['csv' => 'Invalid template. Required columns: first_name, class, gender, father_name.']);
        }
        $classes = array_change_key_case(DB::pairs('SELECT name, id FROM classes'));
        $ok = 0;
        $errors = [];
        $line = 1;
        $sid = session_id_current();
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $r = array_combine($head, array_pad(array_slice($row, 0, count($head)), count($head), ''));
            $r = array_map(fn($v) => trim((string)$v), $r);
            try {
                $classId = $classes[strtolower($r['class'] ?? '')] ?? null;
                if (!$classId) {
                    throw new RuntimeException("Unknown class '{$r['class']}'");
                }
                $sectionId = !empty($r['section']) ? DB::val('SELECT id FROM sections WHERE class_id=? AND name=?', [$classId, $r['section']]) : null;
                $err = validate($r, ['first_name' => 'required|max:60', 'gender' => 'required|in:male,female,other', 'dob' => 'date', 'b_form' => 'cnic|unique:students,b_form', 'father_name' => 'required|max:120', 'father_cnic' => 'cnic', 'admission_date' => 'date']);
                if ($err) {
                    throw new RuntimeException(implode(' ', $err));
                }
                DB::tx(function () use ($r, $classId, $sectionId, $sid) {
                    $pid = !empty($r['father_cnic']) ? DB::val('SELECT id FROM parents WHERE father_cnic=?', [$r['father_cnic']]) : null;
                    if (!$pid) {
                        $pid = DB::insert('parents', nullify(['father_name' => $r['father_name'], 'father_cnic' => $r['father_cnic'] ?? '', 'father_phone' => $r['father_phone'] ?? '', 'mother_name' => $r['mother_name'] ?? '', 'address' => $r['address'] ?? '']));
                    }
                    $id = DB::insert('students', nullify([
                        'reg_no' => next_number('student'), 'parent_id' => $pid, 'first_name' => $r['first_name'], 'last_name' => $r['last_name'] ?? '', 'gender' => $r['gender'],
                        'dob' => $r['dob'] ?? '', 'b_form' => $r['b_form'] ?? '', 'phone' => $r['phone'] ?? '', 'address' => $r['address'] ?? '', 'city' => $r['city'] ?? '',
                        'admission_date' => $r['admission_date'] ?: date('Y-m-d'), 'class_id' => $classId, 'section_id' => $sectionId, 'roll_no' => $r['roll_no'] ?? '', 'session_id' => $sid, 'status' => 'active',
                    ]));
                    DB::insert('admissions', ['student_id' => $id, 'session_id' => $sid, 'class_id' => $classId, 'section_id' => $sectionId, 'admission_date' => $r['admission_date'] ?: date('Y-m-d'), 'remarks' => 'Imported', 'created_by' => Auth::id()]);
                    $this->history($id, 'admission', 'Imported from CSV');
                });
                $ok++;
            } catch (Throwable $e) {
                $errors[] = "Line $line: " . $e->getMessage();
            }
        }
        fclose($fh);
        audit('import', 'students', null, "Imported $ok rows, " . count($errors) . ' errors');
        flash($ok ? 'success' : 'warning', sprintf(t('%d students imported.'), $ok));
        foreach (array_slice($errors, 0, 15) as $er) {
            flash('danger', $er);
        }
        redirect(url('students/import'));
    }

    /* ------------------------------------------------------------ helpers */
    private function history(int $studentId, string $action, string $details, ?int $classId = null, ?int $sectionId = null): int
    {
        $s = DB::row('SELECT class_id, section_id, session_id FROM students WHERE id=?', [$studentId]);
        return DB::insert('student_history', ['student_id' => $studentId, 'session_id' => $s['session_id'] ?? null, 'class_id' => $classId ?? $s['class_id'] ?? null,
            'section_id' => $sectionId ?? $s['section_id'] ?? null, 'action' => $action, 'details' => mb_substr($details, 0, 500), 'created_by' => Auth::id()]);
    }
}
