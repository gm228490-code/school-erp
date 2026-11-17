<?php
declare(strict_types=1);

/** Global search across the main record types the user may access */
final class SearchController
{
    public function index(): void
    {
        $q = trim((string)input('q', ''));
        $groups = [];
        if (mb_strlen($q) >= 2) {
            $like = "%$q%";
            if (can('students.view')) {
                $sql = "SELECT s.id, CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) AS title, CONCAT(s.reg_no,' · ',c.name,' ',IFNULL(sec.name,''),' · ',s.status) AS sub FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id
                    WHERE (s.reg_no LIKE ? OR CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) LIKE ? OR s.b_form LIKE ? OR p.father_name LIKE ? OR p.father_phone LIKE ? OR p.father_cnic LIKE ?)";
                $p = [$like, $like, $like, $like, $like, $like];
                if (Auth::scoped()) {
                    $ids = Auth::teacherSectionIds();
                    $sql .= ' AND s.section_id IN ' . DB::in($ids);
                    $p = array_merge($p, $ids);
                }
                $groups['Students'] = ['bi-people', array_map(fn($r) => $r + ['url' => url('students/show', ['id' => $r['id']])], DB::all($sql . ' LIMIT 20', $p))];
            }
            if (can('teachers.view')) {
                $groups['Teachers'] = ['bi-person-workspace', array_map(fn($r) => $r + ['url' => url('employees/show', ['type' => 'teacher', 'id' => $r['id']])],
                    DB::all("SELECT id, name AS title, CONCAT(employee_no,' · ',IFNULL(phone,'')) AS sub FROM teachers WHERE name LIKE ? OR employee_no LIKE ? OR cnic LIKE ? OR phone LIKE ? LIMIT 10", [$like, $like, $like, $like]))];
            }
            if (can('staff.view')) {
                $groups['Staff'] = ['bi-person-gear', array_map(fn($r) => $r + ['url' => url('employees/show', ['type' => 'staff', 'id' => $r['id']])],
                    DB::all("SELECT id, name AS title, CONCAT(employee_no,' · ',IFNULL(phone,'')) AS sub FROM staff WHERE name LIKE ? OR employee_no LIKE ? OR cnic LIKE ? OR phone LIKE ? LIMIT 10", [$like, $like, $like, $like]))];
            }
            if (can('fees.view')) {
                $groups['Invoices'] = ['bi-receipt', array_map(fn($r) => $r + ['url' => url('fees/invoice', ['id' => $r['id']])],
                    DB::all("SELECT i.id, CONCAT(i.invoice_no,' — ',i.title) AS title, CONCAT(s.first_name,' ',IFNULL(s.last_name,''),' · ',i.status) AS sub FROM fee_invoices i JOIN students s ON s.id=i.student_id WHERE i.invoice_no LIKE ? LIMIT 10", [$like]))];
                $groups['Receipts'] = ['bi-cash', array_map(fn($r) => $r + ['url' => url('fees/receipt', ['id' => $r['id']])],
                    DB::all("SELECT p.id, p.receipt_no AS title, CONCAT(s.first_name,' ',IFNULL(s.last_name,''),' · ',p.paid_on,' · ',p.amount) AS sub FROM fee_payments p JOIN students s ON s.id=p.student_id WHERE p.receipt_no LIKE ? OR p.reference LIKE ? LIMIT 10", [$like, $like]))];
            }
            if (can('library.view')) {
                $groups['Books'] = ['bi-book', array_map(fn($r) => $r + ['url' => url('crud', ['res' => 'books', 'q' => $q])],
                    DB::all("SELECT id, title, CONCAT(IFNULL(author,''),' · ISBN ',IFNULL(isbn,'-'),' · ',available,' available') AS sub FROM books WHERE title LIKE ? OR isbn LIKE ? OR author LIKE ? LIMIT 10", [$like, $like, $like]))];
            }
            if (can('users.manage')) {
                $groups['Users'] = ['bi-person-lock', array_map(fn($r) => $r + ['url' => url('crud', ['res' => 'users', 'q' => $q])],
                    DB::all("SELECT id, name AS title, CONCAT('@',username) AS sub FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? LIMIT 10", [$like, $like, $like]))];
            }
            $groups = array_filter($groups, fn($g) => $g[1]);
        }
        view('search/index', ['q' => $q, 'groups' => $groups, 'title' => t('Search')]);
    }
}
