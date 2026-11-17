<?php
declare(strict_types=1);

final class LibraryController
{
    private const MEMBER_SQL = "CASE bi.member_type WHEN 'student' THEN (SELECT CONCAT(first_name,' ',IFNULL(last_name,''),' (',reg_no,')') FROM students WHERE id=bi.member_id)
        WHEN 'teacher' THEN (SELECT CONCAT(name,' (',employee_no,')') FROM teachers WHERE id=bi.member_id) ELSE (SELECT CONCAT(name,' (',employee_no,')') FROM staff WHERE id=bi.member_id) END";

    public function index(): void
    {
        authorize('library.view');
        $status = (string)input('status', 'issued');
        $w = ['1=1'];
        $p = [];
        if ($status === 'overdue') {
            $w[] = "bi.status='issued' AND bi.due_date<?";
            $p[] = date('Y-m-d');
        } elseif (in_array($status, ['issued', 'returned', 'lost'], true)) {
            $w[] = 'bi.status=?';
            $p[] = $status;
        }
        if (($q = trim((string)input('q', ''))) !== '') {
            $w[] = '(b.title LIKE ? OR b.isbn LIKE ? OR ' . self::MEMBER_SQL . ' LIKE ?)';
            array_push($p, "%$q%", "%$q%", "%$q%");
        }
        $where = implode(' AND ', $w);
        $from = 'book_issues bi JOIN books b ON b.id=bi.book_id';
        $pg = paginate("SELECT COUNT(*) FROM $from WHERE $where", "SELECT bi.*, b.title, b.isbn, " . self::MEMBER_SQL . " AS member FROM $from WHERE $where ORDER BY bi.id DESC", $p, 25);
        $stats = [
            'books' => (int)DB::val('SELECT COALESCE(SUM(quantity),0) FROM books'), 'available' => (int)DB::val('SELECT COALESCE(SUM(available),0) FROM books'),
            'issued' => (int)DB::val("SELECT COUNT(*) FROM book_issues WHERE status='issued'"), 'overdue' => (int)DB::val("SELECT COUNT(*) FROM book_issues WHERE status='issued' AND due_date<?", [date('Y-m-d')]),
        ];
        view('library/index', ['p' => $pg, 'status' => $status, 'stats' => $stats, 'title' => t('Issue / Return Books')]);
    }

    public function issue_form(): void
    {
        authorize('library.issue');
        echo render('library/issue_form', ['bookId' => input_int('book_id')], null);
    }

    public function issue(): void
    {
        authorize('library.issue');
        $bookId = input_int('book_id', 0);
        $type = (string)input('member_type');
        $issueDate = input_date('issue_date', date('Y-m-d'));
        $due = input_date('due_date');
        validate_or_fail(['book_id' => $bookId, 'member_type' => $type, 'due_date' => $due], ['book_id' => 'required|exists:books,id', 'member_type' => 'required|in:student,teacher,staff', 'due_date' => 'required|date']);
        if ($type === 'student') {
            $memberId = input_int('student_id', 0);
            if (!DB::val("SELECT id FROM students WHERE id=? AND status='active'", [$memberId])) {
                throw new ValidationException(['student_id' => 'Select a valid active student.']);
            }
        } else {
            if (!preg_match('/^(teacher|staff):(\d+)$/', (string)input('employee'), $m) || $m[1] !== $type) {
                throw new ValidationException(['employee' => 'Select the employee.']);
            }
            $memberId = (int)$m[2];
        }
        if ($due < $issueDate) {
            throw new ValidationException(['due_date' => 'Due date must be after issue date.']);
        }
        if (DB::val("SELECT id FROM book_issues WHERE book_id=? AND member_type=? AND member_id=? AND status='issued'", [$bookId, $type, $memberId])) {
            throw new ValidationException(['book_id' => 'This member already has a copy of this book.']);
        }
        $id = DB::tx(function () use ($bookId, $type, $memberId, $issueDate, $due) {
            $avail = (int)DB::val('SELECT available FROM books WHERE id=? FOR UPDATE', [$bookId]);
            if ($avail < 1) {
                throw new ValidationException(['book_id' => 'No copies available.']);
            }
            DB::q('UPDATE books SET available=available-1 WHERE id=?', [$bookId]);
            return DB::insert('book_issues', ['book_id' => $bookId, 'member_type' => $type, 'member_id' => $memberId, 'issue_date' => $issueDate, 'due_date' => $due, 'status' => 'issued',
                'remarks' => mb_substr((string)input('remarks', ''), 0, 255) ?: null, 'issued_by' => Auth::id()]);
        });
        audit('issue', 'library', (string)$id);
        json_out(['ok' => true, 'message' => t('Book issued.')]);
    }

    public function return_form(): void
    {
        authorize('library.issue');
        $bi = DB::row('SELECT bi.*, b.title, ' . self::MEMBER_SQL . " AS member FROM book_issues bi JOIN books b ON b.id=bi.book_id WHERE bi.id=? AND bi.status='issued'", [input_int('id', 0)]);
        if (!$bi) {
            abort(404);
        }
        $late = max(0, (int)floor((strtotime(date('Y-m-d')) - strtotime($bi['due_date'])) / 86400));
        echo render('library/return_form', ['bi' => $bi, 'late' => $late, 'fine' => $late * (float)setting('library_fine_per_day', '10')], null);
    }

    public function return(): void
    {
        authorize('library.issue');
        $bi = DB::row("SELECT bi.*, b.title FROM book_issues bi JOIN books b ON b.id=bi.book_id WHERE bi.id=? AND bi.status='issued'", [input_int('id', 0)]);
        if (!$bi) {
            abort(404);
        }
        $lost = input('lost') === '1';
        $fine = max(0, round((float)input('fine', 0), 2));
        $paid = (bool)input('fine_paid');
        $date = input_date('return_date', date('Y-m-d'));
        DB::tx(function () use ($bi, $lost, $fine, $paid, $date) {
            DB::update('book_issues', ['status' => $lost ? 'lost' : 'returned', 'return_date' => $date, 'fine' => $fine, 'fine_paid' => $paid ? 1 : 0, 'remarks' => mb_substr((string)input('remarks', $bi['remarks'] ?? ''), 0, 255) ?: null], 'id=?', [$bi['id']]);
            if ($lost) {
                DB::q('UPDATE books SET quantity=GREATEST(0, quantity-1) WHERE id=?', [$bi['book_id']]);
            } else {
                DB::q('UPDATE books SET available=LEAST(quantity, available+1) WHERE id=?', [$bi['book_id']]);
            }
            if ($fine > 0 && $paid) {
                $cat = DB::val("SELECT id FROM income_categories WHERE name='Library Fines'") ?: DB::insert('income_categories', ['name' => 'Library Fines']);
                DB::insert('incomes', ['category_id' => $cat, 'title' => 'Library fine: ' . mb_substr($bi['title'], 0, 120), 'amount' => $fine, 'income_date' => $date, 'method' => 'cash', 'reference' => 'BI-' . $bi['id'], 'created_by' => Auth::id()]);
            }
        });
        audit($lost ? 'lost' : 'return', 'library', (string)$bi['id'], 'Fine ' . $fine);
        json_out(['ok' => true, 'message' => $lost ? t('Book marked as lost.') : t('Book returned.')]);
    }
}
