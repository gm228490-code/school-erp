<?php
declare(strict_types=1);

/** Teachers and Staff share one controller (?type=teacher|staff) */
final class EmployeesController
{
    private string $type;
    private string $table;
    private string $perm;

    public function __construct()
    {
        $this->type = input('type') === 'staff' ? 'staff' : 'teacher';
        $this->table = $this->type === 'teacher' ? 'teachers' : 'staff';
        $this->perm = $this->type === 'teacher' ? 'teachers' : 'staff';
    }

    private function label(): string
    {
        return $this->type === 'teacher' ? t('Teachers') : t('Staff');
    }

    public static function fields(string $type): array
    {
        return [
            'h1' => ['label' => 'Personal Information', 'type' => 'heading'],
            'name' => ['label' => 'Full Name', 'rules' => 'required|max:120', 'col' => 4],
            'father_name' => ['label' => "Father's / Husband's Name", 'rules' => 'max:120', 'col' => 4],
            'gender' => ['label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], 'rules' => 'required', 'col' => 4],
            'dob' => ['label' => 'Date of Birth', 'type' => 'date', 'col' => 4],
            'cnic' => ['label' => 'CNIC', 'rules' => 'cnic', 'col' => 4, 'placeholder' => '00000-0000000-0'],
            'phone' => ['label' => 'Phone', 'rules' => 'phone', 'col' => 4],
            'email' => ['label' => 'Email', 'type' => 'email', 'col' => 6],
            'photo' => ['label' => 'Photograph', 'type' => 'image', 'col' => 6],
            'address' => ['label' => 'Address', 'type' => 'textarea', 'rows' => 2, 'col' => 12],
            'h2' => ['label' => 'Professional', 'type' => 'heading'],
            'qualification' => ['label' => 'Qualification', 'rules' => 'max:150', 'col' => 4, 'placeholder' => 'e.g. M.Sc Physics, B.Ed'],
            'specialization' => ['label' => $type === 'teacher' ? 'Specialization / Subjects' : 'Skills', 'rules' => 'max:150', 'col' => 4],
            'experience_years' => ['label' => 'Experience (years)', 'type' => 'decimal', 'step' => '0.5', 'rules' => 'numeric|min:0|max:60', 'col' => 4],
            'joining_date' => ['label' => 'Joining Date', 'type' => 'date', 'col' => 4],
            'department_id' => ['label' => 'Department', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM departments ORDER BY name'), 'col' => 4],
            'designation_id' => ['label' => 'Designation', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM designations ORDER BY name'), 'col' => 4],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'resigned' => 'Resigned'], 'rules' => 'required', 'col' => 4, 'default' => 'active'],
            'h3' => ['label' => 'Salary Details', 'type' => 'heading'],
            'basic_salary' => ['label' => 'Basic Salary', 'type' => 'decimal', 'rules' => 'required|numeric|min:0', 'col' => 3],
            'allowances' => ['label' => 'Allowances', 'type' => 'decimal', 'rules' => 'numeric|min:0', 'col' => 3, 'default' => 0],
            'bank_name' => ['label' => 'Bank Name', 'rules' => 'max:100', 'col' => 3],
            'bank_account' => ['label' => 'Account / IBAN', 'rules' => 'max:50', 'col' => 3],
        ];
    }

    public function index(): void
    {
        authorize("{$this->perm}.view");
        $w = ['1=1'];
        $p = [];
        if (($q = trim((string)input('q', ''))) !== '') {
            $w[] = '(t.name LIKE ? OR t.employee_no LIKE ? OR t.cnic LIKE ? OR t.phone LIKE ? OR t.qualification LIKE ?)';
            array_push($p, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
        }
        $status = (string)input('status', 'active');
        if (in_array($status, ['active', 'inactive', 'resigned'], true)) {
            $w[] = 't.status=?';
            $p[] = $status;
        }
        if ($d = input_int('department_id')) {
            $w[] = 't.department_id=?';
            $p[] = $d;
        }
        $where = implode(' AND ', $w);
        $from = "{$this->table} t LEFT JOIN departments d ON d.id=t.department_id LEFT JOIN designations g ON g.id=t.designation_id";
        $order = order_by(['no' => 't.employee_no', 'name' => 't.name', 'joining' => 't.joining_date', 'salary' => 't.basic_salary'], 't.name');
        $pg = paginate("SELECT COUNT(*) FROM $from WHERE $where", "SELECT t.*, d.name AS dept, g.name AS desig FROM $from WHERE $where ORDER BY $order", $p, 25);
        view('employees/index', ['p' => $pg, 'type' => $this->type, 'perm' => $this->perm, 'title' => $this->label()]);
    }

    public function form(): void
    {
        $id = input_int('id');
        authorize($this->perm . ($id ? '.edit' : '.create'));
        $row = $id ? DB::row("SELECT * FROM {$this->table} WHERE id=?", [$id]) : null;
        if ($id && !$row) {
            abort(404);
        }
        echo render('employees/form', ['row' => $row, 'type' => $this->type, 'fields' => self::fields($this->type)], null);
    }

    public function save(): void
    {
        $id = input_int('id');
        authorize($this->perm . ($id ? '.edit' : '.create'));
        $old = $id ? DB::row("SELECT * FROM {$this->table} WHERE id=?", [$id]) : null;
        if ($id && !$old) {
            abort(404);
        }
        $fields = self::fields($this->type);
        $data = [];
        $rules = [];
        $labels = [];
        foreach ($fields as $k => $f) {
            if (in_array($f['type'] ?? 'text', ['heading', 'image'], true)) {
                continue;
            }
            $data[$k] = is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : null;
            $rules[$k] = ($f['rules'] ?? '') . (($f['type'] ?? '') === 'date' ? '|date' : '') . (($f['type'] ?? '') === 'email' ? '|email' : '');
            $labels[$k] = $f['label'];
        }
        $rules['cnic'] .= "|unique:{$this->table},cnic" . ($id ? ",$id" : '');
        $rules['gender'] .= '|in:male,female,other';
        $rules['status'] .= '|in:active,inactive,resigned';
        $rules['department_id'] = 'exists:departments,id';
        $rules['designation_id'] = 'exists:designations,id';
        validate_or_fail($data, array_map(fn($r) => trim($r, '|'), $rules), $labels);
        $data = nullify($data);
        $data['allowances'] ??= 0;
        $data['experience_years'] ??= 0;
        if ($photo = upload_file('photo', 'employees', 'image')) {
            $data['photo'] = $photo;
            if ($old) {
                delete_upload($old['photo']);
            }
        }
        $creds = null;
        $newId = DB::tx(function () use ($id, $data, $old, &$creds) {
            if ($id) {
                DB::update($this->table, $data, 'id=?', [$id]);
                if ($old['user_id']) {
                    DB::update('users', ['name' => $data['name'], 'status' => $data['status'] === 'active' ? 'active' : 'inactive'], 'id=?', [$old['user_id']]);
                }
                audit('update', $this->table, (string)$id, $data['name']);
                return $id;
            }
            $data['employee_no'] = next_number($this->type);
            $nid = DB::insert($this->table, $data);
            audit('create', $this->table, (string)$nid, $data['employee_no'] . ' ' . $data['name']);
            if (input('create_login')) {
                $role = $this->type === 'teacher' ? 'teacher' : (string)input('login_role', 'receptionist');
                if (!DB::val('SELECT id FROM roles WHERE slug=? AND slug NOT IN (\'super_admin\',\'parent\',\'student\')', [$role])) {
                    $role = 'receptionist';
                }
                $creds = AccountService::ensureLogin($this->table, $nid, $role, $data['name'], strtolower($data['employee_no']), $data['email'], $data['phone']);
            }
            return $nid;
        });
        $msg = ($id ? t('Record updated.') : t('Record created.'));
        if ($creds) {
            json_out(['ok' => true, 'sticky' => true, 'message' => $msg . ' ' . t('Login') . ' — ' . t('Username') . ": {$creds[0]} | " . t('Password') . ": {$creds[1]}", 'redirect' => url('employees/show', ['type' => $this->type, 'id' => $newId])]);
        }
        json_out(['ok' => true, 'message' => $msg]);
    }

    public function show(): void
    {
        authorize("{$this->perm}.view");
        $e = DB::row("SELECT t.*, d.name AS dept, g.name AS desig, u.username, u.last_login, r.name AS role_name FROM {$this->table} t LEFT JOIN departments d ON d.id=t.department_id
            LEFT JOIN designations g ON g.id=t.designation_id LEFT JOIN users u ON u.id=t.user_id LEFT JOIN roles r ON r.id=u.role_id WHERE t.id=?", [input_int('id', 0)]);
        if (!$e) {
            abort(404);
        }
        $id = (int)$e['id'];
        $sid = session_id_current();
        $sess = current_session();
        $data = [
            'e' => $e, 'type' => $this->type, 'perm' => $this->perm,
            'att' => DB::pairs('SELECT status, COUNT(*) FROM employee_attendance WHERE emp_type=? AND emp_id=? AND att_date BETWEEN ? AND ? GROUP BY status', [$this->type, $id, $sess['start_date'] ?? '1970-01-01', $sess['end_date'] ?? '2999-12-31']),
            'leaves' => DB::all('SELECT * FROM leaves WHERE emp_type=? AND emp_id=? ORDER BY from_date DESC LIMIT 20', [$this->type, $id]),
            'salaries' => can('finance.salary') ? DB::all('SELECT * FROM salaries WHERE emp_type=? AND emp_id=? ORDER BY salary_month DESC LIMIT 24', [$this->type, $id]) : [],
            'docs' => DB::all('SELECT * FROM employee_documents WHERE emp_type=? AND emp_id=? ORDER BY id DESC', [$this->type, $id]),
            'subjects' => [], 'classTeacher' => [], 'timetable' => [], 'performance' => [],
            'title' => $e['name'],
        ];
        if ($this->type === 'teacher') {
            $data['subjects'] = DB::all('SELECT c.name AS class_name, s.name AS section_name, sb.name AS subject FROM class_subjects cs JOIN classes c ON c.id=cs.class_id JOIN sections s ON s.id=cs.section_id JOIN subjects sb ON sb.id=cs.subject_id WHERE cs.teacher_id=? ORDER BY c.numeric_order, s.name', [$id]);
            $data['classTeacher'] = DB::all('SELECT c.name AS class_name, s.name AS section_name FROM sections s JOIN classes c ON c.id=s.class_id WHERE s.class_teacher_id=?', [$id]);
            $data['timetable'] = DB::all('SELECT tt.day_of_week, p.name AS period, p.start_time, sb.name AS subject, c.name AS class_name, sec.name AS section_name FROM timetables tt JOIN periods p ON p.id=tt.period_id JOIN sections sec ON sec.id=tt.section_id JOIN classes c ON c.id=sec.class_id LEFT JOIN subjects sb ON sb.id=tt.subject_id WHERE tt.teacher_id=? AND tt.session_id=? ORDER BY tt.day_of_week, p.sort_order', [$id, $sid]);
            $data['performance'] = DB::all('SELECT * FROM teacher_performance WHERE teacher_id=? ORDER BY review_date DESC', [$id]);
        }
        view('employees/show', $data);
    }

    public function idcard(): void
    {
        authorize("{$this->perm}.view");
        $ids = array_filter(array_map('intval', (array)($_GET['ids'] ?? [input_int('id', 0)])));
        $rows = DB::all("SELECT t.*, g.name AS desig, d.name AS dept FROM {$this->table} t LEFT JOIN designations g ON g.id=t.designation_id LEFT JOIN departments d ON d.id=t.department_id WHERE " . (input('all') ? "t.status='active'" : 't.id IN ' . DB::in($ids)) . ' ORDER BY t.name', input('all') ? [] : $ids);
        view('employees/idcards', ['rows' => $rows, 'type' => $this->type, 'title' => t('ID Cards'), 'noHeader' => true], 'print');
    }

    public function login(): void
    {
        authorize("{$this->perm}.edit");
        if (!can('users.manage') && $this->type !== 'teacher') {
            abort(403);
        }
        $e = DB::row("SELECT * FROM {$this->table} WHERE id=?", [input_int('id', 0)]);
        if (!$e) {
            abort(404);
        }
        $role = $this->type === 'teacher' ? 'teacher' : 'receptionist';
        [$u, $pw] = AccountService::ensureLogin($this->table, (int)$e['id'], $role, $e['name'], strtolower($e['employee_no']), $e['email'], $e['phone']);
        json_out(['ok' => true, 'sticky' => true, 'reload' => true, 'message' => t('Login') . ' — ' . t('Username') . ": $u | " . t('Password') . ": $pw" . ($this->type === 'staff' ? ' (' . t('Role: Receptionist. Change it in Users if needed.') . ')' : '')]);
    }

    public function upload_document(): void
    {
        authorize("{$this->perm}.edit");
        $id = input_int('id', 0);
        if (!DB::val("SELECT id FROM {$this->table} WHERE id=?", [$id])) {
            abort(404);
        }
        $title = trim((string)input('title'));
        validate_or_fail(['title' => $title], ['title' => 'required|max:150']);
        $path = upload_file('file', 'documents', 'doc', true);
        DB::insert('employee_documents', ['emp_type' => $this->type, 'emp_id' => $id, 'title' => $title, 'file_path' => $path, 'original_name' => mb_substr($_FILES['file']['name'] ?? '', 0, 255), 'uploaded_by' => Auth::id()]);
        flash('success', t('Document uploaded.'));
        redirect(url('employees/show', ['type' => $this->type, 'id' => $id]));
    }

    public function delete_document(): void
    {
        authorize("{$this->perm}.edit");
        $d = DB::row('SELECT * FROM employee_documents WHERE id=? AND emp_type=?', [input_int('doc_id', 0), $this->type]);
        if (!$d) {
            abort(404);
        }
        DB::delete('employee_documents', 'id=?', [$d['id']]);
        delete_upload($d['file_path']);
        json_out(['ok' => true, 'message' => t('Document deleted.')]);
    }

    public function delete(): void
    {
        authorize("{$this->perm}.delete");
        $e = DB::row("SELECT * FROM {$this->table} WHERE id=?", [input_int('id', 0)]);
        if (!$e) {
            abort(404);
        }
        if (DB::val('SELECT COUNT(*) FROM salaries WHERE emp_type=? AND emp_id=?', [$this->type, $e['id']])) {
            throw new HttpException(t('Salary records exist. Mark the employee as Resigned/Inactive instead of deleting.'), 400);
        }
        DB::tx(function () use ($e) {
            DB::delete('employee_attendance', 'emp_type=? AND emp_id=?', [$this->type, $e['id']]);
            DB::delete('leaves', 'emp_type=? AND emp_id=?', [$this->type, $e['id']]);
            DB::delete('employee_documents', 'emp_type=? AND emp_id=?', [$this->type, $e['id']]);
            DB::delete($this->table, 'id=?', [$e['id']]);
            if ($e['user_id']) {
                DB::delete('users', 'id=?', [$e['user_id']]);
            }
        });
        delete_upload($e['photo']);
        audit('delete', $this->table, (string)$e['id'], $e['name']);
        json_out(['ok' => true, 'message' => t('Deleted successfully'), 'redirect' => url('employees', ['type' => $this->type])]);
    }

    public function export(): void
    {
        authorize("{$this->perm}.view");
        $rows = DB::all("SELECT t.employee_no, t.name, t.father_name, t.gender, t.cnic, t.phone, t.email, t.qualification, t.experience_years, t.joining_date, d.name, g.name, t.basic_salary, t.allowances, t.status
            FROM {$this->table} t LEFT JOIN departments d ON d.id=t.department_id LEFT JOIN designations g ON g.id=t.designation_id ORDER BY t.name");
        export_table($this->table, ['Employee No', 'Name', 'Father Name', 'Gender', 'CNIC', 'Phone', 'Email', 'Qualification', 'Experience', 'Joining Date', 'Department', 'Designation', 'Basic Salary', 'Allowances', 'Status'], $rows, input('format') === 'xls' ? 'xls' : 'csv');
    }
}
