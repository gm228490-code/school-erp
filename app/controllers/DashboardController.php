<?php
declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        if (Auth::isPortal()) {
            redirect(url('portal'));
        }
        if (!can('dashboard.view')) {
            // Users without dashboard permission land on their first accessible module
            view('dashboard/minimal', ['title' => t('Welcome')]);
        }
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $sid = session_id_current();
        $d = [];

        $scopeSql = '';
        $scopeP = [];
        if (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $scopeSql = ' AND section_id IN ' . DB::in($ids);
            $scopeP = $ids;
        }

        $d['students'] = (int)DB::val("SELECT COUNT(*) FROM students WHERE status='active'" . $scopeSql, $scopeP);
        $d['boys'] = (int)DB::val("SELECT COUNT(*) FROM students WHERE status='active' AND gender='male'" . $scopeSql, $scopeP);
        $d['girls'] = $d['students'] - $d['boys'];
        $d['teachers'] = (int)DB::val("SELECT COUNT(*) FROM teachers WHERE status='active'");
        $d['staff'] = (int)DB::val("SELECT COUNT(*) FROM staff WHERE status='active'");
        $d['classes'] = (int)DB::val('SELECT COUNT(*) FROM classes');
        $d['sections'] = (int)DB::val('SELECT COUNT(*) FROM sections');
        $d['subjects'] = (int)DB::val('SELECT COUNT(*) FROM subjects');

        $att = DB::pairs('SELECT status, COUNT(*) FROM student_attendance WHERE att_date=?' . $scopeSql . ' GROUP BY status', array_merge([$today], $scopeP));
        $d['att_present'] = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0);
        $d['att_absent'] = (int)($att['absent'] ?? 0);
        $d['att_leave'] = (int)($att['leave'] ?? 0);
        $d['att_marked'] = array_sum(array_map('intval', $att));
        $d['att_pct'] = $d['att_marked'] ? round($d['att_present'] / $d['att_marked'] * 100, 1) : 0;
        $empAtt = DB::pairs('SELECT status, COUNT(*) FROM employee_attendance WHERE att_date=? GROUP BY status', [$today]);
        $d['emp_present'] = (int)($empAtt['present'] ?? 0) + (int)($empAtt['late'] ?? 0);
        $d['emp_marked'] = array_sum(array_map('intval', $empAtt));

        if (can('fees.view')) {
            $d['fee_today'] = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE paid_on=?', [$today]);
            $d['fee_today_count'] = (int)DB::val('SELECT COUNT(*) FROM fee_payments WHERE paid_on=?', [$today]);
            $d['fee_month'] = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE paid_on>=?', [$monthStart]);
            $d['fee_pending'] = (float)DB::val("SELECT COALESCE(SUM(net_amount-paid_amount),0) FROM fee_invoices WHERE status IN ('unpaid','partial')");
            $d['fee_defaulters'] = (int)DB::val("SELECT COUNT(DISTINCT student_id) FROM fee_invoices WHERE status IN ('unpaid','partial') AND due_date<?", [$today]);
        }
        if (can('finance.view')) {
            $d['exp_month'] = (float)DB::val('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date>=?', [$monthStart])
                + (float)DB::val("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='paid' AND paid_on>=?", [$monthStart]);
            // 12-month income vs expense
            $labels = $inc = $exp = [];
            for ($i = 11; $i >= 0; $i--) {
                $m = date('Y-m', strtotime("first day of -$i month"));
                $labels[] = date('M y', strtotime($m . '-01'));
                $inc[] = (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE DATE_FORMAT(paid_on,'%Y-%m')=?", [$m])
                    + (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM incomes WHERE DATE_FORMAT(income_date,'%Y-%m')=?", [$m]);
                $exp[] = (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?", [$m])
                    + (float)DB::val("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='paid' AND DATE_FORMAT(paid_on,'%Y-%m')=?", [$m]);
            }
            $d['chart_fin'] = ['labels' => $labels, 'income' => $inc, 'expense' => $exp];
        }

        // Attendance trend: last 14 days with records
        $trend = DB::all("SELECT att_date, SUM(status IN ('present','late')) AS p, SUM(status='absent') AS a, COUNT(*) AS t
                          FROM student_attendance WHERE att_date>=?" . $scopeSql . ' GROUP BY att_date ORDER BY att_date', array_merge([date('Y-m-d', strtotime('-20 days'))], $scopeP));
        $d['chart_att'] = [
            'labels' => array_map(fn($r) => date('d M', strtotime($r['att_date'])), $trend),
            'present' => array_map(fn($r) => (int)$r['p'], $trend),
            'absent' => array_map(fn($r) => (int)$r['a'], $trend),
        ];
        $d['chart_class'] = DB::pairs("SELECT c.name, COUNT(s.id) FROM classes c LEFT JOIN students s ON s.class_id=c.id AND s.status='active' GROUP BY c.id ORDER BY c.numeric_order, c.name");

        $d['recent_admissions'] = DB::all("SELECT s.id, s.first_name, s.last_name, s.reg_no, s.photo, s.admission_date, c.name AS class_name, sec.name AS section_name
            FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id WHERE 1=1" . str_replace('section_id', 's.section_id', $scopeSql) . ' ORDER BY s.id DESC LIMIT 6', $scopeP);
        $d['upcoming_exams'] = DB::all("SELECT e.*, (SELECT MIN(es.exam_date) FROM exam_subjects es WHERE es.exam_id=e.id AND es.exam_date>=?) AS next_paper
            FROM exams e WHERE e.session_id=? AND (e.end_date IS NULL OR e.end_date>=?) ORDER BY COALESCE(e.start_date,'9999-12-31') LIMIT 5", [$today, $sid, $today]);
        $d['notices'] = DB::all('SELECT * FROM notices WHERE publish_date<=? AND (expiry_date IS NULL OR expiry_date>=?) ORDER BY publish_date DESC, id DESC LIMIT 5', [$today, $today]);
        $d['notifications'] = DB::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 5', [Auth::id()]);
        $d['pending_leaves'] = can('leaves.manage') ? (int)DB::val("SELECT COUNT(*) FROM leaves WHERE status='pending'") : 0;
        $d['overdue_books'] = can('library.view') ? (int)DB::val("SELECT COUNT(*) FROM book_issues WHERE status='issued' AND due_date<?", [$today]) : 0;
        $d['recent_payments'] = can('fees.view') ? DB::all("SELECT p.*, s.first_name, s.last_name, s.reg_no FROM fee_payments p JOIN students s ON s.id=p.student_id ORDER BY p.id DESC LIMIT 5") : [];

        // Teacher: today's schedule
        $d['my_schedule'] = [];
        if ($t = Auth::teacher()) {
            $d['my_schedule'] = DB::all('SELECT p.name AS period, p.start_time, p.end_time, sb.name AS subject, c.name AS class_name, sec.name AS section_name, tt.room
                FROM timetables tt JOIN periods p ON p.id=tt.period_id JOIN sections sec ON sec.id=tt.section_id JOIN classes c ON c.id=sec.class_id LEFT JOIN subjects sb ON sb.id=tt.subject_id
                WHERE tt.teacher_id=? AND tt.day_of_week=? AND tt.session_id=? ORDER BY p.sort_order', [$t['id'], (int)date('N'), $sid]);
        }
        view('dashboard/index', ['d' => $d, 'title' => t('Dashboard')]);
    }
}
