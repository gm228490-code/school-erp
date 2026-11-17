<?php
declare(strict_types=1);

/**
 * Printable / exportable reports. Each report builds [headers, rows, footer] and is rendered by output().
 * ?print=1 → print layout (Save as PDF), ?export=xls|csv → spreadsheet download.
 */
final class ReportsController
{
    public function index(): void
    {
        authorize('reports.view');
        view('reports/index', ['title' => t('Reports')]);
    }

    private function output(string $key, string $title, array $headers, array $rows, array $filters = [], ?array $footer = null, string $subtitle = '', bool $landscape = false): never
    {
        if ($fmt = input('export')) {
            audit('export', 'reports', $key);
            export_table($key, $headers, $footer ? array_merge($rows, [$footer]) : $rows, $fmt === 'xls' ? 'xls' : 'csv');
        }
        $data = compact('key', 'title', 'headers', 'rows', 'footer', 'filters', 'subtitle', 'landscape');
        if (input('print')) {
            view('reports/print', $data, 'print');
        }
        view('reports/table', $data);
    }

    private function range(): array
    {
        return [input_date('from', date('Y-m-01')), input_date('to', date('Y-m-d'))];
    }

    /* --------------------------------------------------------------- students */
    public function admissions(): void
    {
        authorize('reports.view');
        [$from, $to] = $this->range();
        $p = [$from, $to];
        $sql = "SELECT a.admission_date, s.reg_no, s.first_name, s.last_name, s.gender, p.father_name, p.father_phone, c.name AS class_name, sec.name AS section_name, s.previous_school, s.status
            FROM admissions a JOIN students s ON s.id=a.student_id JOIN classes c ON c.id=a.class_id LEFT JOIN sections sec ON sec.id=a.section_id LEFT JOIN parents p ON p.id=s.parent_id WHERE a.admission_date BETWEEN ? AND ?";
        if ($c = input_int('class_id')) { $sql .= ' AND a.class_id=?'; $p[] = $c; }
        $rows = array_map(fn($r) => [fmt_date($r['admission_date']), $r['reg_no'], student_name($r), ucfirst($r['gender']), $r['father_name'], $r['father_phone'], trim($r['class_name'] . ' ' . $r['section_name']), $r['previous_school'], ucfirst($r['status'])],
            DB::all($sql . ' ORDER BY a.admission_date, s.reg_no', $p));
        $this->output('admissions', t('Student Admission Report'), ['Date', 'Reg No', 'Student', 'Gender', 'Father', 'Phone', 'Class', 'Previous School', 'Status'], $rows,
            ['from' => $from, 'to' => $to, 'class_id' => true], ['', '', 'Total: ' . count($rows), '', '', '', '', '', ''], fmt_date($from) . ' — ' . fmt_date($to));
    }

    public function class_list(): void
    {
        authorize('students.view');
        $classId = input_int('class_id');
        $p = [];
        $sql = "SELECT s.roll_no, s.reg_no, s.first_name, s.last_name, s.gender, s.dob, s.b_form, p.father_name, p.father_phone, c.name AS class_name, sec.name AS section_name, s.address
            FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id WHERE s.status='active'";
        if ($classId) { $sql .= ' AND s.class_id=?'; $p[] = $classId; }
        if ($sec = input_int('section_id')) { $sql .= ' AND s.section_id=?'; $p[] = $sec; }
        if (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $sql .= ' AND s.section_id IN ' . DB::in($ids);
            $p = array_merge($p, $ids);
        }
        $rows = array_map(fn($r) => [$r['roll_no'], $r['reg_no'], student_name($r), ucfirst($r['gender']), fmt_date($r['dob']), $r['b_form'], $r['father_name'], $r['father_phone'], trim($r['class_name'] . ' ' . $r['section_name'])],
            DB::all($sql . ' ORDER BY c.numeric_order, sec.name, s.roll_no+0, s.first_name', $p));
        $boys = count(array_filter($rows, fn($r) => $r[3] === 'Male'));
        $sub = $classId ? DB::val('SELECT name FROM classes WHERE id=?', [$classId]) . ($sec ? ' - ' . DB::val('SELECT name FROM sections WHERE id=?', [$sec]) : '') : t('All Classes');
        $this->output('class_list', t('Class-wise Student List'), ['Roll', 'Reg No', 'Student', 'Gender', 'DOB', 'B-Form', 'Father', 'Phone', 'Class'], $rows,
            ['class_id' => true, 'section_id' => true], ['', '', 'Total: ' . count($rows) . " (Boys $boys, Girls " . (count($rows) - $boys) . ')', '', '', '', '', '', ''], $sub);
    }

    public function strength(): void
    {
        authorize('reports.view');
        $rows = array_map(fn($r) => [$r['class_name'], $r['section_name'] ?? '-', (int)$r['boys'], (int)$r['girls'], (int)$r['total'], (int)$r['capacity']],
            DB::all("SELECT c.name AS class_name, sec.name AS section_name, sec.capacity, SUM(s.gender='male') AS boys, SUM(s.gender<>'male') AS girls, COUNT(s.id) AS total
                FROM sections sec JOIN classes c ON c.id=sec.class_id LEFT JOIN students s ON s.section_id=sec.id AND s.status='active' GROUP BY sec.id ORDER BY c.numeric_order, sec.name"));
        $this->output('strength', t('Class Strength Report'), ['Class', 'Section', 'Boys', 'Girls', 'Total', 'Capacity'], $rows, [],
            ['Total', '', array_sum(array_column($rows, 2)), array_sum(array_column($rows, 3)), array_sum(array_column($rows, 4)), array_sum(array_column($rows, 5))]);
    }

    /* --------------------------------------------------------------- fees */
    public function outstanding(): void
    {
        authorize('fees.view');
        $p = [];
        $sql = "SELECT s.reg_no, s.first_name, s.last_name, c.name AS class_name, sec.name AS section_name, p.father_name, p.father_phone, COUNT(i.id) AS n, MIN(i.due_date) AS oldest, SUM(i.net_amount-i.paid_amount) AS due
            FROM fee_invoices i JOIN students s ON s.id=i.student_id JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id
            WHERE i.status IN ('unpaid','partial')";
        if ($c = input_int('class_id')) { $sql .= ' AND s.class_id=?'; $p[] = $c; }
        if (input('overdue')) { $sql .= ' AND i.due_date<?'; $p[] = date('Y-m-d'); }
        $data = DB::all($sql . ' GROUP BY s.id ORDER BY c.numeric_order, sec.name, s.roll_no+0', $p);
        $rows = array_map(fn($r) => [$r['reg_no'], student_name($r), trim($r['class_name'] . ' ' . $r['section_name']), $r['father_name'], $r['father_phone'], (int)$r['n'], fmt_date($r['oldest']), number_format((float)$r['due'], 0, '.', '')], $data);
        $this->output('outstanding', t('Outstanding Fees Report'), ['Reg No', 'Student', 'Class', 'Father', 'Phone', 'Invoices', 'Oldest Due', 'Amount Due'], $rows,
            ['class_id' => true, 'overdue' => true], ['', 'Students: ' . count($rows), '', '', '', array_sum(array_column($rows, 5)), '', money(array_sum(array_column($data, 'due')))], t('As of') . ' ' . fmt_date(date('Y-m-d')));
    }

    public function collection(): void
    {
        authorize('fees.view');
        [$from, $to] = $this->range();
        $group = input('group') === 'month' ? 'month' : 'day';
        $fmt = $group === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $data = DB::all("SELECT DATE_FORMAT(paid_on, '$fmt') AS k, COUNT(*) AS n, SUM(method='cash') AS cash_n, SUM(CASE WHEN method='cash' THEN amount END) AS cash, SUM(CASE WHEN method<>'cash' THEN amount END) AS other, SUM(fine) AS fine, SUM(amount) AS total
            FROM fee_payments WHERE paid_on BETWEEN ? AND ? GROUP BY k ORDER BY k", [$from, $to]);
        $rows = array_map(fn($r) => [$group === 'month' ? fmt_month($r['k']) : fmt_date($r['k'], 'D d-M-Y'), (int)$r['n'], number_format((float)$r['cash'], 0, '.', ''), number_format((float)$r['other'], 0, '.', ''), number_format((float)$r['fine'], 0, '.', ''), number_format((float)$r['total'], 0, '.', '')], $data);
        $this->output('fee_collection', $group === 'month' ? t('Monthly Fee Collection Report') : t('Daily Fee Collection Report'), [$group === 'month' ? 'Month' : 'Date', 'Receipts', 'Cash', 'Bank / Online', 'Late Fines', 'Total'], $rows,
            ['from' => $from, 'to' => $to, 'group' => true], ['Total', array_sum(array_column($rows, 1)), money(array_sum(array_column($data, 'cash'))), money(array_sum(array_column($data, 'other'))), money(array_sum(array_column($data, 'fine'))), money(array_sum(array_column($data, 'total')))],
            fmt_date($from) . ' — ' . fmt_date($to));
    }

    public function class_fees(): void
    {
        authorize('fees.view');
        $month = valid_month((string)input('month')) ? (string)input('month') : date('Y-m');
        $data = DB::all("SELECT c.name, COUNT(i.id) AS n, SUM(i.net_amount) AS billed, SUM(i.paid_amount) AS paid, SUM(CASE WHEN i.status IN ('unpaid','partial') THEN i.net_amount-i.paid_amount ELSE 0 END) AS due
            FROM fee_invoices i JOIN classes c ON c.id=i.class_id WHERE i.fee_month=? AND i.status<>'cancelled' GROUP BY c.id ORDER BY c.numeric_order", [$month]);
        $rows = array_map(fn($r) => [$r['name'], (int)$r['n'], money($r['billed'], false), money($r['paid'], false), money($r['due'], false), $r['billed'] > 0 ? round($r['paid'] / $r['billed'] * 100, 1) . '%' : '-'], $data);
        $b = array_sum(array_column($data, 'billed'));
        $pd = array_sum(array_column($data, 'paid'));
        $this->output('class_fees', t('Class-wise Fee Status'), ['Class', 'Invoices', 'Billed', 'Collected', 'Outstanding', 'Recovery'], $rows, ['month' => $month],
            ['Total', array_sum(array_column($rows, 1)), money($b), money($pd), money(array_sum(array_column($data, 'due'))), $b > 0 ? round($pd / $b * 100, 1) . '%' : '-'], fmt_month($month));
    }

    /* --------------------------------------------------------------- finance */
    public function expenses(): void
    {
        authorize('finance.view');
        [$from, $to] = $this->range();
        $data = DB::all('SELECT e.expense_date, ec.name AS cat, e.title, e.paid_to, e.method, e.reference, e.amount FROM expenses e JOIN expense_categories ec ON ec.id=e.category_id WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date, e.id', [$from, $to]);
        $rows = array_map(fn($r) => [fmt_date($r['expense_date']), $r['cat'], $r['title'], $r['paid_to'], ucfirst($r['method']), $r['reference'], number_format((float)$r['amount'], 0, '.', '')], $data);
        $sal = (float)DB::val("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='paid' AND paid_on BETWEEN ? AND ?", [$from, $to]);
        $this->output('expenses', t('Expense Report'), ['Date', 'Category', 'Title', 'Paid To', 'Method', 'Reference', 'Amount'], $rows, ['from' => $from, 'to' => $to],
            ['', '', 'Expenses: ' . money(array_sum(array_column($data, 'amount'))), 'Salaries paid: ' . money($sal), '', 'Total', money(array_sum(array_column($data, 'amount')) + $sal)], fmt_date($from) . ' — ' . fmt_date($to));
    }

    public function income(): void
    {
        authorize('finance.view');
        [$from, $to] = $this->range();
        $data = DB::all('SELECT i.income_date, ic.name AS cat, i.title, i.method, i.reference, i.amount FROM incomes i JOIN income_categories ic ON ic.id=i.category_id WHERE i.income_date BETWEEN ? AND ? ORDER BY i.income_date, i.id', [$from, $to]);
        $fees = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE paid_on BETWEEN ? AND ?', [$from, $to]);
        $rows = array_merge([['', 'Fee Collection', 'Student fee receipts (all)', '', '', number_format($fees, 0, '.', '')]],
            array_map(fn($r) => [fmt_date($r['income_date']), $r['cat'], $r['title'], ucfirst($r['method']), $r['reference'], number_format((float)$r['amount'], 0, '.', '')], $data));
        $this->output('income', t('Income Report'), ['Date', 'Category', 'Title', 'Method', 'Reference', 'Amount'], $rows, ['from' => $from, 'to' => $to],
            ['', '', '', '', 'Total', money($fees + array_sum(array_column($data, 'amount')))], fmt_date($from) . ' — ' . fmt_date($to));
    }

    public function salary(): void
    {
        authorize('finance.salary');
        $month = valid_month((string)input('month')) ? (string)input('month') : date('Y-m');
        $data = DB::all('SELECT s.*, ' . employee_name_sql('s') . ' AS name FROM salaries s WHERE s.salary_month=? ORDER BY s.emp_type, name', [$month]);
        $rows = array_map(fn($r) => [$r['slip_no'], $r['name'], ucfirst($r['emp_type']), money($r['basic'], false), money($r['allowances'] + $r['bonus'], false), money($r['deductions'], false), money($r['net_salary'], false), ucfirst($r['status']), fmt_date($r['paid_on'])], $data);
        $this->output('salary', t('Salary Report'), ['Slip', 'Employee', 'Type', 'Basic', 'Allowances + Bonus', 'Deductions', 'Net', 'Status', 'Paid On'], $rows, ['month' => $month],
            ['', 'Employees: ' . count($rows), '', '', '', '', money(array_sum(array_column($data, 'net_salary'))), '', ''], fmt_month($month), true);
    }

    /* --------------------------------------------------------------- HR */
    public function employees(): void
    {
        $type = input('type') === 'staff' ? 'staff' : 'teacher';
        authorize($type === 'teacher' ? 'teachers.view' : 'staff.view');
        $table = $type === 'teacher' ? 'teachers' : 'staff';
        $data = DB::all("SELECT t.*, g.name AS desig, d.name AS dept FROM $table t LEFT JOIN designations g ON g.id=t.designation_id LEFT JOIN departments d ON d.id=t.department_id WHERE t.status='active' ORDER BY t.name");
        $rows = array_map(fn($r) => [$r['employee_no'], $r['name'], $r['desig'], $r['dept'], $r['qualification'], (float)$r['experience_years'], $r['phone'], $r['cnic'], fmt_date($r['joining_date'])], $data);
        if ($type === 'teacher') {
            foreach ($data as $i => $r) {
                $rows[$i][] = implode(', ', DB::col("SELECT DISTINCT CONCAT(sb.name,' (',c.name,')') FROM class_subjects cs JOIN subjects sb ON sb.id=cs.subject_id JOIN classes c ON c.id=cs.class_id WHERE cs.teacher_id=?", [$r['id']]));
            }
        }
        $headers = ['ID', 'Name', 'Designation', 'Department', 'Qualification', 'Experience', 'Phone', 'CNIC', 'Joined'];
        if ($type === 'teacher') {
            $headers[] = 'Subjects';
        }
        $this->output('employees', $type === 'teacher' ? t('Teacher Report') : t('Staff Report'), $headers, $rows, ['type' => $type], null, t('Active') . ': ' . count($rows), true);
    }

    public function employee_attendance(): void
    {
        authorize('attendance.view');
        $month = valid_month((string)input('month')) ? (string)input('month') : date('Y-m');
        redirect(url('attendance/report', ['mode' => 'employees', 'type' => input('type') === 'staff' ? 'staff' : 'teacher', 'month' => $month]));
    }

    /* --------------------------------------------------------------- library & transport */
    public function library(): void
    {
        authorize('library.view');
        $mode = input('mode') === 'books' ? 'books' : 'issued';
        if ($mode === 'books') {
            $data = DB::all('SELECT b.*, bc.name AS cat FROM books b LEFT JOIN book_categories bc ON bc.id=b.category_id ORDER BY bc.name, b.title');
            $rows = array_map(fn($r) => [$r['title'], $r['author'], $r['isbn'], $r['cat'], $r['rack_no'], (int)$r['quantity'], (int)$r['available'], (int)$r['quantity'] - (int)$r['available']], $data);
            $this->output('library_books', t('Library Stock Report'), ['Title', 'Author', 'ISBN', 'Category', 'Rack', 'Quantity', 'Available', 'Issued'], $rows, ['mode' => true],
                ['Titles: ' . count($rows), '', '', '', '', array_sum(array_column($rows, 5)), array_sum(array_column($rows, 6)), array_sum(array_column($rows, 7))]);
        }
        $data = DB::all("SELECT bi.*, b.title, CASE bi.member_type WHEN 'student' THEN (SELECT CONCAT(first_name,' ',IFNULL(last_name,''),' (',reg_no,')') FROM students WHERE id=bi.member_id)
                WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=bi.member_id) ELSE (SELECT name FROM staff WHERE id=bi.member_id) END AS member
            FROM book_issues bi JOIN books b ON b.id=bi.book_id WHERE bi.status='issued' ORDER BY bi.due_date");
        $rows = array_map(fn($r) => [$r['title'], $r['member'], ucfirst($r['member_type']), fmt_date($r['issue_date']), fmt_date($r['due_date']), $r['due_date'] < date('Y-m-d') ? (int)((strtotime(date('Y-m-d')) - strtotime($r['due_date'])) / 86400) . ' days overdue' : 'On time'], $data);
        $this->output('library_issued', t('Library Report — Books Issued'), ['Book', 'Member', 'Type', 'Issued', 'Due', 'Status'], $rows, ['mode' => true], null, t('As of') . ' ' . fmt_date(date('Y-m-d')));
    }

    public function transport(): void
    {
        authorize('transport.view');
        $data = DB::all("SELECT r.name AS route, v.vehicle_no, v.driver_name, v.driver_phone, s.reg_no, s.first_name, s.last_name, c.name AS class_name, p.father_phone, s.address, r.monthly_fee
            FROM students s JOIN transport_routes r ON r.id=s.route_id LEFT JOIN transport_vehicles v ON v.id=r.vehicle_id JOIN classes c ON c.id=s.class_id LEFT JOIN parents p ON p.id=s.parent_id
            WHERE s.status='active'" . (input_int('route_id') ? ' AND r.id=' . (int)input_int('route_id') : '') . ' ORDER BY r.name, c.numeric_order, s.first_name');
        $rows = array_map(fn($r) => [$r['route'], $r['vehicle_no'], $r['driver_name'] . ' ' . $r['driver_phone'], $r['reg_no'], student_name($r), $r['class_name'], $r['father_phone'], $r['address'], money($r['monthly_fee'], false)], $data);
        $this->output('transport', t('Transport Report'), ['Route', 'Vehicle', 'Driver', 'Reg No', 'Student', 'Class', 'Parent Phone', 'Address', 'Fee'], $rows, ['route_id' => true],
            ['Students: ' . count($rows), '', '', '', '', '', '', '', money(array_sum(array_column($data, 'monthly_fee')))], '', true);
    }
}
