<?php
declare(strict_types=1);

final class FeesController
{
    /* ------------------------------------------------- invoices list */
    public function index(): void
    {
        authorize('fees.view');
        $w = ['1=1'];
        $p = [];
        if (in_array($st = (string)input('status', ''), ['unpaid', 'partial', 'paid', 'cancelled'], true)) { $w[] = 'i.status=?'; $p[] = $st; }
        if ($st === 'due') { $w[] = "i.status IN ('unpaid','partial')"; }
        if ($st === 'overdue') { $w[] = "i.status IN ('unpaid','partial') AND i.due_date<?"; $p[] = date('Y-m-d'); }
        if (valid_month((string)input('month'))) { $w[] = 'i.fee_month=?'; $p[] = input('month'); }
        if ($c = input_int('class_id')) { $w[] = 's.class_id=?'; $p[] = $c; }
        if ($s = input_int('student_id')) { $w[] = 'i.student_id=?'; $p[] = $s; }
        if (($q = trim((string)input('q', ''))) !== '') {
            $w[] = "(i.invoice_no LIKE ? OR s.reg_no LIKE ? OR s.first_name LIKE ? OR CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) LIKE ?)";
            array_push($p, "%$q%", "%$q%", "%$q%", "%$q%");
        }
        $where = implode(' AND ', $w);
        $from = 'fee_invoices i JOIN students s ON s.id=i.student_id JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id';
        $order = order_by(['no' => 'i.invoice_no', 'due' => 'i.due_date', 'net' => 'i.net_amount', 'student' => 's.first_name'], 'i.id DESC');
        $pg = paginate("SELECT COUNT(*) FROM $from WHERE $where", "SELECT i.*, s.first_name, s.last_name, s.reg_no, c.name AS class_name, sec.name AS section_name FROM $from WHERE $where ORDER BY $order", $p, 25);
        $totals = DB::row("SELECT COALESCE(SUM(CASE WHEN i.status<>'cancelled' THEN i.net_amount END),0) AS net, COALESCE(SUM(i.paid_amount),0) AS paid FROM $from WHERE $where", $p);
        if (input('export')) {
            $rows = array_map(fn($r) => [$r['invoice_no'], $r['reg_no'], student_name($r), $r['class_name'], $r['title'], $r['due_date'], $r['net_amount'], $r['paid_amount'], $r['net_amount'] - $r['paid_amount'], $r['status']],
                DB::all("SELECT i.*, s.first_name, s.last_name, s.reg_no, c.name AS class_name FROM $from WHERE $where ORDER BY $order", $p));
            export_table('invoices', ['Invoice', 'Reg No', 'Student', 'Class', 'Title', 'Due Date', 'Net', 'Paid', 'Balance', 'Status'], $rows, input('export') === 'xls' ? 'xls' : 'csv');
        }
        view('fees/index', ['p' => $pg, 'totals' => $totals, 'title' => t('Fee Invoices')]);
    }

    public function invoice(): void
    {
        authorize('fees.view');
        $inv = $this->findInvoice();
        $items = DB::all('SELECT * FROM fee_invoice_items WHERE invoice_id=? ORDER BY id', [$inv['id']]);
        $payments = DB::all('SELECT p.*, u.name AS by_name FROM fee_payments p LEFT JOIN users u ON u.id=p.received_by WHERE p.invoice_id=? ORDER BY p.id', [$inv['id']]);
        if (input('print')) {
            view('fees/challan', ['inv' => $inv, 'items' => $items, 'title' => t('Fee Invoice / Challan'), 'noHeader' => true], 'print');
        }
        view('fees/invoice', ['inv' => $inv, 'items' => $items, 'payments' => $payments, 'fine' => FeeService::lateFine($inv), 'title' => t('Invoice') . ' ' . $inv['invoice_no']]);
    }

    private function findInvoice(?int $id = null): array
    {
        $inv = DB::row("SELECT i.*, s.first_name, s.last_name, s.reg_no, s.roll_no, s.photo, s.parent_id, c.name AS class_name, sec.name AS section_name, p.father_name, p.father_phone, ses.name AS session_name
            FROM fee_invoices i JOIN students s ON s.id=i.student_id LEFT JOIN classes c ON c.id=i.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id
            LEFT JOIN academic_sessions ses ON ses.id=i.session_id WHERE i.id=?", [$id ?? input_int('id', 0)]);
        if (!$inv) {
            abort(404);
        }
        return $inv;
    }

    /** Bulk challan print for a month / class */
    public function challans(): void
    {
        authorize('fees.view');
        $month = (string)input('month');
        $w = ["i.status IN ('unpaid','partial')"];
        $p = [];
        if (valid_month($month)) { $w[] = 'i.fee_month=?'; $p[] = $month; }
        if ($c = input_int('class_id')) { $w[] = 'i.class_id=?'; $p[] = $c; }
        $ids = DB::col('SELECT i.id FROM fee_invoices i JOIN students s ON s.id=i.student_id WHERE ' . implode(' AND ', $w) . ' ORDER BY i.class_id, s.roll_no+0 LIMIT 500', $p);
        $list = [];
        foreach ($ids as $id) {
            $inv = $this->findInvoice((int)$id);
            $list[] = ['inv' => $inv, 'items' => DB::all('SELECT * FROM fee_invoice_items WHERE invoice_id=?', [$id])];
        }
        view('fees/challans', ['list' => $list, 'title' => t('Fee Challans'), 'noHeader' => true], 'print');
    }

    /* ------------------------------------------------- manual invoice */
    public function create_form(): void
    {
        authorize('fees.manage');
        echo render('fees/create_form', ['studentId' => input_int('student_id')], null);
    }

    public function create_invoice(): void
    {
        authorize('fees.manage');
        $sid = input_int('student_id', 0);
        $student = DB::row("SELECT * FROM students WHERE id=? AND status='active'", [$sid]);
        if (!$student) {
            throw new ValidationException(['student_id' => 'Select a valid active student.']);
        }
        $title = trim((string)input('title'));
        $due = input_date('due_date');
        validate_or_fail(['title' => $title, 'due_date' => $due], ['title' => 'required|max:150', 'due_date' => 'required|date']);
        $items = [];
        $cats = DB::pairs('SELECT id, name FROM fee_categories');
        foreach ((array)($_POST['item_cat'] ?? []) as $i => $cat) {
            $amt = (float)($_POST['item_amount'][$i] ?? 0);
            $disc = (float)($_POST['item_discount'][$i] ?? 0);
            $desc = trim((string)($_POST['item_desc'][$i] ?? ''));
            if ($amt <= 0) {
                continue;
            }
            if ($disc < 0 || $disc > $amt) {
                throw new ValidationException(['item_discount' => 'Discount must be between 0 and the item amount.']);
            }
            $cid = isset($cats[(int)$cat]) ? (int)$cat : null;
            $items[] = ['fee_category_id' => $cid, 'description' => $desc ?: ($cats[$cid] ?? 'Fee'), 'amount' => $amt, 'discount' => $disc];
        }
        if (!$items) {
            throw new ValidationException(['item_amount' => 'Add at least one item with an amount.']);
        }
        $id = FeeService::createInvoice($student, $title, null, $items, date('Y-m-d'), $due, mb_substr((string)input('remarks', ''), 0, 255) ?: null);
        audit('create', 'fee_invoices', (string)$id, $title);
        json_out(['ok' => true, 'message' => t('Invoice created.'), 'redirect' => url('fees/invoice', ['id' => $id])]);
    }

    public function cancel(): void
    {
        authorize('fees.delete');
        $inv = $this->findInvoice();
        if ((float)$inv['paid_amount'] > 0) {
            throw new HttpException(t('This invoice has payments. Cancel the payments first.'), 400);
        }
        DB::update('fee_invoices', ['status' => 'cancelled', 'remarks' => mb_substr('Cancelled: ' . (string)input('reason', ''), 0, 255)], 'id=?', [$inv['id']]);
        audit('cancel', 'fee_invoices', (string)$inv['id'], $inv['invoice_no']);
        json_out(['ok' => true, 'message' => t('Invoice cancelled.')]);
    }

    /* ------------------------------------------------- structure */
    public function structure(): void
    {
        authorize('fees.manage');
        $sid = session_id_current();
        $classes = DB::all('SELECT id, name FROM classes ORDER BY numeric_order, name');
        $cats = DB::all("SELECT * FROM fee_categories WHERE name<>'Transport Fee' ORDER BY id");
        $amounts = [];
        foreach (DB::all('SELECT class_id, fee_category_id, amount FROM fee_structures WHERE session_id=?', [$sid]) as $r) {
            $amounts[$r['class_id']][$r['fee_category_id']] = $r['amount'];
        }
        view('fees/structure', compact('classes', 'cats', 'amounts') + ['title' => t('Class-wise Fee Structure')]);
    }

    public function save_structure(): void
    {
        authorize('fees.manage');
        $sid = session_id_current();
        $validClasses = array_map('intval', DB::col('SELECT id FROM classes'));
        $validCats = array_map('intval', DB::col('SELECT id FROM fee_categories'));
        $n = 0;
        DB::tx(function () use ($sid, $validClasses, $validCats, &$n) {
            foreach ((array)($_POST['fee'] ?? []) as $classId => $cats) {
                foreach ((array)$cats as $catId => $amt) {
                    if (!in_array((int)$classId, $validClasses, true) || !in_array((int)$catId, $validCats, true)) {
                        continue;
                    }
                    $amt = trim((string)$amt);
                    if ($amt === '' || (float)$amt <= 0) {
                        DB::q('DELETE FROM fee_structures WHERE session_id=? AND class_id=? AND fee_category_id=?', [$sid, $classId, $catId]);
                        continue;
                    }
                    if (!is_numeric($amt)) {
                        throw new ValidationException(['fee' => 'All amounts must be numbers.']);
                    }
                    DB::upsert('fee_structures', ['session_id' => $sid, 'class_id' => (int)$classId, 'fee_category_id' => (int)$catId, 'amount' => round((float)$amt, 2)], ['amount']);
                    $n++;
                }
            }
        });
        audit('update', 'fee_structures', null, "Saved $n amounts");
        flash('success', t('Fee structure saved.'));
        redirect(url('fees/structure'));
    }

    /* ------------------------------------------------- generation */
    public function generate(): void
    {
        authorize('fees.manage');
        $recent = DB::all("SELECT fee_month, COUNT(*) AS n, SUM(net_amount) AS net, SUM(paid_amount) AS paid FROM fee_invoices WHERE fee_month IS NOT NULL AND status<>'cancelled' GROUP BY fee_month ORDER BY fee_month DESC LIMIT 12");
        view('fees/generate', ['recent' => $recent, 'title' => t('Generate Monthly Fees')]);
    }

    public function do_generate(): void
    {
        authorize('fees.manage');
        $month = (string)input('month');
        $due = input_date('due_date');
        $classIds = array_map('intval', (array)($_POST['classes'] ?? []));
        $extra = array_map('intval', (array)($_POST['extra'] ?? []));
        validate_or_fail(['month' => $month, 'due_date' => $due], ['month' => 'required|month', 'due_date' => 'required|date']);
        if (!$classIds) {
            throw new ValidationException(['classes' => 'Select at least one class.']);
        }
        [$c, $s, $e] = FeeService::generateMonthly($month, $classIds, $extra, date('Y-m-d'), $due);
        flash('success', sprintf(t('%d invoices generated for %s. %d already existed and were skipped.'), $c, fmt_month($month), $s));
        if ($e) {
            flash('warning', sprintf(t('%d students have no fee amounts configured (check Fee Structure).'), $e));
        }
        if ($c && input('notify')) {
            $uids = DB::col("SELECT p.user_id FROM fee_invoices i JOIN students s ON s.id=i.student_id JOIN parents p ON p.id=s.parent_id WHERE i.fee_month=? AND p.user_id IS NOT NULL", [$month]);
            notify(array_map('intval', $uids), 'Fee invoice issued', 'Fee invoice for ' . fmt_month($month) . ' has been issued. Due date: ' . fmt_date($due) . '.', 'index.php?r=portal/fees', 'fee');
        }
        redirect(url('fees', ['month' => $month]));
    }

    /* ------------------------------------------------- collection */
    public function collect(): void
    {
        authorize('fees.collect');
        $student = null;
        $invoices = [];
        if ($sid = input_int('student_id')) {
            $student = DB::row("SELECT s.*, c.name AS class_name, sec.name AS section_name, p.father_name, p.father_phone FROM students s JOIN classes c ON c.id=s.class_id
                LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id WHERE s.id=?", [$sid]);
            if ($student) {
                $invoices = DB::all("SELECT * FROM fee_invoices WHERE student_id=? AND status IN ('unpaid','partial') ORDER BY due_date, id", [$sid]);
                foreach ($invoices as &$i) {
                    $i['late_fine'] = FeeService::lateFine($i);
                }
                unset($i);
            }
        }
        $today = DB::row('SELECT COUNT(*) AS n, COALESCE(SUM(amount),0) AS total FROM fee_payments WHERE paid_on=?', [date('Y-m-d')]);
        view('fees/collect', ['student' => $student, 'invoices' => $invoices, 'today' => $today,
            'recent' => $student ? DB::all('SELECT * FROM fee_payments WHERE student_id=? ORDER BY id DESC LIMIT 5', [$student['id']]) : [],
            'title' => t('Collect Fee')]);
    }

    public function pay(): void
    {
        authorize('fees.collect');
        $studentId = input_int('student_id', 0);
        $method = (string)input('method', 'cash');
        $date = input_date('paid_on', date('Y-m-d'));
        validate_or_fail(['method' => $method, 'paid_on' => $date, 'reference' => input('reference')], ['method' => 'required|in:cash,bank,cheque,online', 'paid_on' => 'required|date', 'reference' => 'max:100']);
        if ($date > date('Y-m-d')) {
            throw new ValidationException(['paid_on' => 'Payment date cannot be in the future.']);
        }
        $amounts = (array)($_POST['pay'] ?? []);
        $fines = (array)($_POST['fine'] ?? []);
        $ids = [];
        DB::tx(function () use ($amounts, $fines, $studentId, $method, $date, &$ids) {
            foreach ($amounts as $invId => $amt) {
                $amt = round((float)$amt, 2);
                $fine = round(max(0, (float)($fines[$invId] ?? 0)), 2);
                if ($amt <= 0) {
                    continue;
                }
                if ((int)DB::val('SELECT student_id FROM fee_invoices WHERE id=?', [(int)$invId]) !== $studentId) {
                    throw new ValidationException(['pay' => 'Invoice does not belong to this student.']);
                }
                $ids[] = FeeService::pay((int)$invId, $amt, $fine, $method, input('reference') ?: null, $date, input('remarks') ?: null);
            }
        });
        if (!$ids) {
            throw new ValidationException(['pay' => 'Enter an amount to collect.']);
        }
        // notify parent
        $s = DB::row('SELECT s.first_name, s.last_name, p.user_id, p.father_phone FROM students s LEFT JOIN parents p ON p.id=s.parent_id WHERE s.id=?', [$studentId]);
        $total = (float)DB::val('SELECT SUM(amount) FROM fee_payments WHERE id IN ' . DB::in($ids), $ids);
        if ($s && $s['user_id']) {
            notify([(int)$s['user_id']], 'Fee payment received', money($total) . ' received for ' . student_name($s) . '. Thank you.', 'index.php?r=portal/fees', 'fee');
        }
        flash('success', sprintf(t('Payment of %s received. %d receipt(s) generated.'), money($total), count($ids)));
        redirect(url('fees/receipt', ['ids' => implode(',', $ids), 'back' => 1]));
    }

    public function receipt(): void
    {
        $ids = array_filter(array_map('intval', explode(',', (string)input('ids', (string)input('id', '')))));
        if (!$ids) {
            abort(404);
        }
        $rows = DB::all("SELECT p.*, i.invoice_no, i.title AS inv_title, i.net_amount, i.paid_amount AS inv_paid, i.fee_month, s.first_name, s.last_name, s.reg_no, s.parent_id, c.name AS class_name, sec.name AS section_name, pa.father_name, u.name AS by_name
            FROM fee_payments p JOIN fee_invoices i ON i.id=p.invoice_id JOIN students s ON s.id=p.student_id LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id
            LEFT JOIN parents pa ON pa.id=s.parent_id LEFT JOIN users u ON u.id=p.received_by WHERE p.id IN " . DB::in($ids) . ' ORDER BY p.id', $ids);
        if (!$rows) {
            abort(404);
        }
        // Access: staff with fees.view, or the parent/student who owns it
        if (Auth::isPortal()) {
            $mine = PortalController::studentIds();
            foreach ($rows as $r) {
                if (!in_array((int)$r['student_id'], $mine, true)) {
                    abort(403);
                }
            }
        } elseif (!can('fees.view') && !can('fees.collect')) {
            abort(403);
        }
        foreach ($rows as &$r) {
            $r['items'] = DB::all('SELECT * FROM fee_invoice_items WHERE invoice_id=?', [$r['invoice_id']]);
        }
        unset($r);
        view('fees/receipt', ['rows' => $rows, 'title' => t('Fee Receipt'), 'noHeader' => true], 'print');
    }

    public function payments(): void
    {
        authorize('fees.view');
        $from = input_date('from', date('Y-m-01'));
        $to = input_date('to', date('Y-m-d'));
        $w = ['p.paid_on BETWEEN ? AND ?'];
        $prm = [$from, $to];
        if (in_array($m = (string)input('method', ''), ['cash', 'bank', 'cheque', 'online'], true)) { $w[] = 'p.method=?'; $prm[] = $m; }
        if ($c = input_int('class_id')) { $w[] = 's.class_id=?'; $prm[] = $c; }
        if ($u = input_int('received_by')) { $w[] = 'p.received_by=?'; $prm[] = $u; }
        if (($q = trim((string)input('q', ''))) !== '') {
            $w[] = "(p.receipt_no LIKE ? OR s.reg_no LIKE ? OR CONCAT(s.first_name,' ',IFNULL(s.last_name,'')) LIKE ? OR p.reference LIKE ?)";
            array_push($prm, "%$q%", "%$q%", "%$q%", "%$q%");
        }
        $where = implode(' AND ', $w);
        $fromSql = 'fee_payments p JOIN students s ON s.id=p.student_id JOIN fee_invoices i ON i.id=p.invoice_id LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN users u ON u.id=p.received_by';
        $sel = 'p.*, s.first_name, s.last_name, s.reg_no, c.name AS class_name, i.invoice_no, i.title AS inv_title, u.name AS by_name';
        if (input('export') || input('print')) {
            $rows = DB::all("SELECT $sel FROM $fromSql WHERE $where ORDER BY p.paid_on, p.id", $prm);
            if (input('print')) {
                view('fees/payments_print', ['rows' => $rows, 'title' => t('Fee Collection Report'), 'subtitle' => fmt_date($from) . ' — ' . fmt_date($to)], 'print');
            }
            export_table('fee_collection', ['Receipt', 'Date', 'Reg No', 'Student', 'Class', 'Invoice', 'Method', 'Reference', 'Fine', 'Amount', 'Received By'],
                array_map(fn($r) => [$r['receipt_no'], $r['paid_on'], $r['reg_no'], student_name($r), $r['class_name'], $r['invoice_no'], $r['method'], $r['reference'], $r['fine'], $r['amount'], $r['by_name']], $rows), input('export') === 'xls' ? 'xls' : 'csv');
        }
        $pg = paginate("SELECT COUNT(*) FROM $fromSql WHERE $where", "SELECT $sel FROM $fromSql WHERE $where ORDER BY p.id DESC", $prm, 25);
        $sum = DB::row("SELECT COALESCE(SUM(p.amount),0) AS total, COUNT(*) AS n FROM $fromSql WHERE $where", $prm);
        $byMethod = DB::pairs("SELECT p.method, SUM(p.amount) FROM $fromSql WHERE $where GROUP BY p.method", $prm);
        view('fees/payments', ['p' => $pg, 'sum' => $sum, 'byMethod' => $byMethod, 'from' => $from, 'to' => $to, 'title' => t('Fee Payments')]);
    }

    /** Reverse a payment (e.g. wrong entry / bounced cheque) */
    public function cancel_payment(): void
    {
        authorize('fees.delete');
        $p = DB::row('SELECT * FROM fee_payments WHERE id=?', [input_int('id', 0)]);
        if (!$p) {
            abort(404);
        }
        DB::tx(function () use ($p) {
            DB::q('UPDATE fee_invoices SET paid_amount=GREATEST(0, paid_amount-?), fine=GREATEST(0, fine-?) WHERE id=?', [$p['amount'], $p['fine'], $p['invoice_id']]);
            DB::delete('fee_payments', 'id=?', [$p['id']]);
            FeeService::refreshStatus((int)$p['invoice_id']);
        });
        audit('reverse', 'fee_payments', (string)$p['id'], 'Reversed receipt ' . $p['receipt_no'] . ' amount ' . $p['amount']);
        json_out(['ok' => true, 'message' => t('Payment reversed.')]);
    }

    /* ------------------------------------------------- ledger */
    public function ledger(): void
    {
        $sid = input_int('student_id', 0);
        if (Auth::isPortal()) {
            if (!in_array($sid, PortalController::studentIds(), true)) {
                abort(403);
            }
        } else {
            authorize('fees.view');
        }
        $s = DB::row('SELECT s.*, c.name AS class_name, sec.name AS section_name, p.father_name FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id WHERE s.id=?', [$sid]);
        if (!$s) {
            abort(404);
        }
        $entries = [];
        foreach (DB::all("SELECT * FROM fee_invoices WHERE student_id=? AND status<>'cancelled'", [$sid]) as $i) {
            $entries[] = ['date' => $i['issue_date'], 'ref' => $i['invoice_no'], 'desc' => $i['title'] . ((float)$i['discount'] > 0 ? ' (disc. ' . money($i['discount'], false) . ')' : ''), 'debit' => (float)$i['gross_amount'] - (float)$i['discount'], 'credit' => 0.0, 'k' => $i['id'] * 10];
            if ((float)$i['fine'] > 0) {
                $entries[] = ['date' => $i['due_date'], 'ref' => $i['invoice_no'], 'desc' => 'Late payment fine', 'debit' => (float)$i['fine'], 'credit' => 0.0, 'k' => $i['id'] * 10 + 1];
            }
        }
        foreach (DB::all('SELECT p.*, i.invoice_no FROM fee_payments p JOIN fee_invoices i ON i.id=p.invoice_id WHERE p.student_id=?', [$sid]) as $p) {
            $entries[] = ['date' => $p['paid_on'], 'ref' => $p['receipt_no'], 'desc' => 'Payment (' . ucfirst($p['method']) . ') against ' . $p['invoice_no'], 'debit' => 0.0, 'credit' => (float)$p['amount'], 'k' => PHP_INT_MAX - 1000000 + $p['id']];
        }
        usort($entries, fn($a, $b) => [$a['date'], $a['k']] <=> [$b['date'], $b['k']]);
        view('fees/ledger', ['s' => $s, 'entries' => $entries, 'title' => t('Student Fee Ledger'), 'subtitle' => student_name($s) . ' (' . $s['reg_no'] . ')'], 'print');
    }

    /* ------------------------------------------------- reminders */
    public function reminders(): void
    {
        authorize('notices.manage');
        $today = date('Y-m-d');
        $rows = DB::all("SELECT s.id, s.first_name, s.last_name, s.reg_no, c.name AS class_name, p.father_name, p.father_phone, p.email, p.user_id, COUNT(i.id) AS invoices,
                SUM(i.net_amount-i.paid_amount) AS due, MIN(i.due_date) AS oldest
            FROM fee_invoices i JOIN students s ON s.id=i.student_id JOIN classes c ON c.id=s.class_id LEFT JOIN parents p ON p.id=s.parent_id
            WHERE i.status IN ('unpaid','partial') AND i.due_date<? AND s.status='active' GROUP BY s.id ORDER BY due DESC", [$today]);
        if (is_post()) {
            $sel = array_map('intval', (array)($_POST['students'] ?? []));
            $sms = (bool)input('sms');
            $n = 0;
            foreach ($rows as $r) {
                if (!in_array((int)$r['id'], $sel, true)) {
                    continue;
                }
                $msg = 'Dear Parent, fee of ' . money($r['due']) . ' for ' . student_name($r) . ' (' . $r['reg_no'] . ') is overdue since ' . fmt_date($r['oldest']) . '. Kindly pay at the earliest. - ' . setting('school_name');
                if ($r['user_id']) {
                    notify([(int)$r['user_id']], 'Fee due reminder', $msg, 'index.php?r=portal/fees', 'fee');
                }
                if ($sms) {
                    if ($r['father_phone']) {
                        send_sms($r['father_phone'], $msg);
                    }
                    if ($r['email']) {
                        send_email($r['email'], 'Fee due reminder', $msg);
                    }
                }
                $n++;
            }
            audit('reminders', 'fees', null, "Sent $n fee reminders");
            flash('success', sprintf(t('Reminders sent for %d students.'), $n));
            redirect(url('fees/reminders'));
        }
        view('fees/reminders', ['rows' => $rows, 'title' => t('Fee Due Alerts')]);
    }
}
