<?php
declare(strict_types=1);

final class FinanceController
{
    /** Totals for a date range */
    public static function totals(string $from, string $to): array
    {
        $fees = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE paid_on BETWEEN ? AND ?', [$from, $to]);
        $other = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM incomes WHERE income_date BETWEEN ? AND ?', [$from, $to]);
        $exp = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?', [$from, $to]);
        $sal = (float)DB::val("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='paid' AND paid_on BETWEEN ? AND ?", [$from, $to]);
        return ['fees' => $fees, 'other' => $other, 'income' => $fees + $other, 'expenses' => $exp, 'salaries' => $sal, 'outflow' => $exp + $sal, 'net' => $fees + $other - $exp - $sal];
    }

    public function index(): void
    {
        authorize('finance.view');
        $from = input_date('from', date('Y-m-01'));
        $to = input_date('to', date('Y-m-d'));
        $tot = self::totals($from, $to);
        $incCats = DB::all('SELECT ic.name, SUM(i.amount) AS total FROM incomes i JOIN income_categories ic ON ic.id=i.category_id WHERE i.income_date BETWEEN ? AND ? GROUP BY ic.id ORDER BY total DESC', [$from, $to]);
        $expCats = DB::all('SELECT ec.name, SUM(e.amount) AS total FROM expenses e JOIN expense_categories ec ON ec.id=e.category_id WHERE e.expense_date BETWEEN ? AND ? GROUP BY ec.id ORDER BY total DESC', [$from, $to]);
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("first day of -$i month"));
            $t = self::totals($m . '-01', date('Y-m-t', strtotime($m . '-01')));
            $months[] = ['label' => date('M y', strtotime($m . '-01')), 'month' => $m] + $t;
        }
        $data = compact('from', 'to', 'tot', 'incCats', 'expCats', 'months') + ['title' => t('Finance Overview')];
        if (input('print')) {
            view('finance/statement_print', $data + ['subtitle' => fmt_date($from) . ' — ' . fmt_date($to), 'title' => t('Income & Expense Statement')], 'print');
        }
        view('finance/index', $data);
    }

    /* ------------------------------------------------- cash book */
    public function cashbook(): void
    {
        authorize('finance.view');
        $date = input_date('date', date('Y-m-d'));
        $opening = self::totals('1970-01-01', date('Y-m-d', strtotime($date . ' -1 day')))['net'];
        $entries = [];
        foreach (DB::all("SELECT p.receipt_no AS ref, p.amount, p.method, CONCAT('Fee: ', s.first_name,' ',IFNULL(s.last_name,''),' (',s.reg_no,')') AS descr, p.created_at FROM fee_payments p JOIN students s ON s.id=p.student_id WHERE p.paid_on=?", [$date]) as $r) {
            $entries[] = $r + ['type' => 'in'];
        }
        foreach (DB::all("SELECT CONCAT('INC-', i.id) AS ref, i.amount, i.method, CONCAT(ic.name, ': ', i.title) AS descr, i.created_at FROM incomes i JOIN income_categories ic ON ic.id=i.category_id WHERE i.income_date=?", [$date]) as $r) {
            $entries[] = $r + ['type' => 'in'];
        }
        foreach (DB::all("SELECT CONCAT('EXP-', e.id) AS ref, e.amount, e.method, CONCAT(ec.name, ': ', e.title) AS descr, e.created_at FROM expenses e JOIN expense_categories ec ON ec.id=e.category_id WHERE e.expense_date=?", [$date]) as $r) {
            $entries[] = $r + ['type' => 'out'];
        }
        foreach (DB::all("SELECT slip_no AS ref, net_salary AS amount, method, CONCAT('Salary ', salary_month, ': ', " . employee_name_sql('s') . ") AS descr, created_at FROM salaries s WHERE status='paid' AND paid_on=?", [$date]) as $r) {
            $entries[] = $r + ['type' => 'out'];
        }
        usort($entries, fn($a, $b) => strcmp((string)$a['created_at'], (string)$b['created_at']));
        $data = ['date' => $date, 'opening' => $opening, 'entries' => $entries, 'title' => t('Daily Cash Book')];
        if (input('print')) {
            view('finance/cashbook_print', $data + ['subtitle' => fmt_date($date, 'l, d F Y')], 'print');
        }
        view('finance/cashbook', $data);
    }

    /* ------------------------------------------------- salaries */
    public function salaries(): void
    {
        authorize('finance.salary');
        $month = valid_month((string)input('month')) ? (string)input('month') : date('Y-m');
        $rows = DB::all('SELECT s.*, ' . employee_name_sql('s') . " AS emp_name,
                CASE s.emp_type WHEN 'teacher' THEN (SELECT employee_no FROM teachers WHERE id=s.emp_id) ELSE (SELECT employee_no FROM staff WHERE id=s.emp_id) END AS emp_no
                FROM salaries s WHERE s.salary_month=? ORDER BY s.emp_type, emp_name", [$month]);
        $pending = (int)DB::val("SELECT (SELECT COUNT(*) FROM teachers WHERE status='active' AND id NOT IN (SELECT emp_id FROM salaries WHERE emp_type='teacher' AND salary_month=?))
            + (SELECT COUNT(*) FROM staff WHERE status='active' AND id NOT IN (SELECT emp_id FROM salaries WHERE emp_type='staff' AND salary_month=?))", [$month, $month]);
        if (input('print')) {
            view('finance/salary_sheet', ['rows' => $rows, 'month' => $month, 'title' => t('Salary Sheet'), 'subtitle' => fmt_month($month), 'landscape' => true], 'print');
        }
        if (input('export')) {
            export_table('salaries_' . $month, ['Slip', 'Type', 'Emp No', 'Name', 'Basic', 'Allowances', 'Bonus', 'Deductions', 'Net', 'Status', 'Paid On'],
                array_map(fn($r) => [$r['slip_no'], $r['emp_type'], $r['emp_no'], $r['emp_name'], $r['basic'], $r['allowances'], $r['bonus'], $r['deductions'], $r['net_salary'], $r['status'], $r['paid_on']], $rows), input('export') === 'xls' ? 'xls' : 'csv');
        }
        view('finance/salaries', ['rows' => $rows, 'month' => $month, 'pending' => $pending, 'title' => t('Salaries')]);
    }

    /** Create unpaid salary records for all active employees for a month (absent-day deduction optional) */
    public function generate_salaries(): void
    {
        authorize('finance.salary');
        $month = (string)input('month');
        validate_or_fail(['month' => $month], ['month' => 'required|month']);
        $deductAbsent = (bool)input('deduct_absent');
        $n = 0;
        DB::tx(function () use ($month, $deductAbsent, &$n) {
            foreach (['teacher' => 'teachers', 'staff' => 'staff'] as $type => $table) {
                foreach (DB::all("SELECT * FROM $table WHERE status='active'") as $e) {
                    if (DB::val('SELECT id FROM salaries WHERE emp_type=? AND emp_id=? AND salary_month=?', [$type, $e['id'], $month])) {
                        continue;
                    }
                    $ded = 0.0;
                    $remarks = null;
                    if ($deductAbsent) {
                        $abs = (int)DB::val("SELECT COUNT(*) FROM employee_attendance WHERE emp_type=? AND emp_id=? AND status='absent' AND DATE_FORMAT(att_date,'%Y-%m')=?", [$type, $e['id'], $month]);
                        if ($abs) {
                            $ded = round((float)$e['basic_salary'] / 30 * $abs, 2);
                            $remarks = "$abs absent day(s) deducted";
                        }
                    }
                    $net = (float)$e['basic_salary'] + (float)$e['allowances'] - $ded;
                    DB::insert('salaries', ['slip_no' => next_number('salary'), 'emp_type' => $type, 'emp_id' => $e['id'], 'salary_month' => $month, 'basic' => $e['basic_salary'],
                        'allowances' => $e['allowances'], 'bonus' => 0, 'deductions' => $ded, 'net_salary' => max(0, $net), 'status' => 'unpaid', 'remarks' => $remarks, 'created_by' => Auth::id()]);
                    $n++;
                }
            }
        });
        audit('generate', 'salaries', null, "Generated $n salary records for $month");
        flash('success', sprintf(t('%d salary records generated for %s.'), $n, fmt_month($month)));
        redirect(url('finance/salaries', ['month' => $month]));
    }

    public function salary_form(): void
    {
        authorize('finance.salary');
        $s = DB::row('SELECT s.*, ' . employee_name_sql('s') . ' AS emp_name FROM salaries s WHERE s.id=?', [input_int('id', 0)]);
        if (!$s) {
            abort(404);
        }
        echo render('finance/salary_form', ['s' => $s], null);
    }

    public function save_salary(): void
    {
        authorize('finance.salary');
        $s = DB::row('SELECT * FROM salaries WHERE id=?', [input_int('id', 0)]);
        if (!$s) {
            abort(404);
        }
        $d = ['basic' => input('basic'), 'allowances' => input('allowances'), 'bonus' => input('bonus'), 'deductions' => input('deductions'), 'remarks' => input('remarks'),
            'pay_now' => input('pay_now'), 'paid_on' => input('paid_on'), 'method' => input('method')];
        validate_or_fail($d, ['basic' => 'required|numeric|min:0', 'allowances' => 'numeric|min:0', 'bonus' => 'numeric|min:0', 'deductions' => 'numeric|min:0', 'remarks' => 'max:255',
            'paid_on' => $d['pay_now'] ? 'required|date' : 'date', 'method' => $d['pay_now'] ? 'required|in:cash,bank,cheque,online' : '']);
        $net = (float)$d['basic'] + (float)$d['allowances'] + (float)$d['bonus'] - (float)$d['deductions'];
        if ($net < 0) {
            throw new ValidationException(['deductions' => 'Deductions exceed the gross salary.']);
        }
        $upd = ['basic' => (float)$d['basic'], 'allowances' => (float)$d['allowances'], 'bonus' => (float)$d['bonus'], 'deductions' => (float)$d['deductions'], 'net_salary' => $net, 'remarks' => $d['remarks'] ?: null];
        if ($d['pay_now'] && $s['status'] !== 'paid') {
            $upd += ['status' => 'paid', 'paid_on' => $d['paid_on'], 'method' => $d['method']];
        }
        DB::update('salaries', $upd, 'id=?', [$s['id']]);
        if (!empty($upd['status'])) {
            $emp = employee($s['emp_type'], (int)$s['emp_id']);
            if ($emp && $emp['user_id']) {
                notify([(int)$emp['user_id']], 'Salary paid', 'Your salary for ' . fmt_month($s['salary_month']) . ' (' . money($net) . ') has been paid.', '', 'salary');
            }
        }
        audit(!empty($upd['status']) ? 'pay' : 'update', 'salaries', (string)$s['id'], 'Net ' . $net);
        json_out(['ok' => true, 'message' => !empty($upd['status']) ? t('Salary paid.') : t('Salary updated.')]);
    }

    public function pay_all(): void
    {
        authorize('finance.salary');
        $month = (string)input('month');
        $method = (string)input('method', 'bank');
        $date = input_date('paid_on', date('Y-m-d'));
        validate_or_fail(['month' => $month, 'method' => $method], ['month' => 'required|month', 'method' => 'required|in:cash,bank,cheque,online']);
        $n = DB::q("UPDATE salaries SET status='paid', paid_on=?, method=? WHERE salary_month=? AND status='unpaid'", [$date, $method, $month])->rowCount();
        audit('pay', 'salaries', null, "Paid $n salaries for $month");
        flash('success', sprintf(t('%d salaries marked as paid.'), $n));
        redirect(url('finance/salaries', ['month' => $month]));
    }

    public function delete_salary(): void
    {
        authorize('finance.salary');
        $s = DB::row('SELECT * FROM salaries WHERE id=?', [input_int('id', 0)]);
        if (!$s) {
            abort(404);
        }
        if ($s['status'] === 'paid') {
            throw new HttpException(t('Paid salary records cannot be deleted.'), 400);
        }
        DB::delete('salaries', 'id=?', [$s['id']]);
        audit('delete', 'salaries', (string)$s['id']);
        json_out(['ok' => true, 'message' => t('Deleted successfully')]);
    }

    public function slip(): void
    {
        authorize('finance.salary');
        $s = DB::row('SELECT * FROM salaries WHERE id=?', [input_int('id', 0)]);
        if (!$s) {
            abort(404);
        }
        $table = $s['emp_type'] === 'teacher' ? 'teachers' : 'staff';
        $e = DB::row("SELECT t.*, g.name AS desig, d.name AS dept FROM $table t LEFT JOIN designations g ON g.id=t.designation_id LEFT JOIN departments d ON d.id=t.department_id WHERE t.id=?", [$s['emp_id']]);
        $att = DB::pairs("SELECT status, COUNT(*) FROM employee_attendance WHERE emp_type=? AND emp_id=? AND DATE_FORMAT(att_date,'%Y-%m')=? GROUP BY status", [$s['emp_type'], $s['emp_id'], $s['salary_month']]);
        view('finance/slip', ['s' => $s, 'e' => $e, 'att' => $att, 'title' => t('Salary Slip') . ' — ' . fmt_month($s['salary_month'])], 'print');
    }
}
