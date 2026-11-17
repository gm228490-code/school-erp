<?php
declare(strict_types=1);

/* =====================================================================
 * FEES
 * ===================================================================== */
final class FeeService
{
    public static function transportCategoryId(): ?int
    {
        $id = DB::val("SELECT id FROM fee_categories WHERE name='Transport Fee' LIMIT 1");
        return $id ? (int)$id : null;
    }

    /**
     * Build invoice line items for a student.
     * @param int[] $categoryIds categories to include (null = all monthly categories)
     */
    public static function buildItems(array $student, int $sessionId, ?array $categoryIds = null, bool $includeTransport = true): array
    {
        $cats = DB::all('SELECT * FROM fee_categories ORDER BY id');
        $structure = DB::pairs('SELECT fee_category_id, amount FROM fee_structures WHERE session_id=? AND class_id=?', [$sessionId, $student['class_id']]);
        $overrides = DB::pairs('SELECT fee_category_id, amount FROM student_fees WHERE student_id=?', [$student['id']]);
        $discounts = DB::all('SELECT * FROM student_discounts WHERE student_id=? AND is_active=1', [$student['id']]);
        $transportCat = self::transportCategoryId();
        $items = [];
        foreach ($cats as $c) {
            $cid = (int)$c['id'];
            if ($cid === $transportCat) {
                continue;
            }
            $wanted = $categoryIds === null ? $c['frequency'] === 'monthly' : in_array($cid, $categoryIds, true);
            if (!$wanted) {
                continue;
            }
            $amount = (float)(array_key_exists($cid, $overrides) ? $overrides[$cid] : ($structure[$cid] ?? 0));
            if ($amount <= 0) {
                continue;
            }
            $items[] = ['fee_category_id' => $cid, 'description' => $c['name'], 'amount' => $amount, 'discount' => 0.0, 'frequency' => $c['frequency']];
        }
        if ($includeTransport && $transportCat && ($categoryIds === null || in_array($transportCat, $categoryIds, true)) && !empty($student['route_id'])) {
            $fare = (float)(array_key_exists($transportCat, $overrides) ? $overrides[$transportCat] : DB::val('SELECT monthly_fee FROM transport_routes WHERE id=?', [$student['route_id']]));
            if ($fare > 0) {
                $items[] = ['fee_category_id' => $transportCat, 'description' => 'Transport Fee', 'amount' => $fare, 'discount' => 0.0, 'frequency' => 'monthly'];
            }
        }
        // Apply discounts / scholarships
        foreach ($discounts as $d) {
            $fixedLeft = (float)$d['value'];
            foreach ($items as &$it) {
                $applies = $d['fee_category_id'] ? (int)$d['fee_category_id'] === $it['fee_category_id'] : $it['frequency'] === 'monthly' && $it['fee_category_id'] !== $transportCat;
                if (!$applies) {
                    continue;
                }
                $remaining = $it['amount'] - $it['discount'];
                if ($d['value_type'] === 'percent') {
                    $it['discount'] += round(min($remaining, $it['amount'] * (float)$d['value'] / 100), 2);
                } elseif ($fixedLeft > 0) {
                    $take = min($remaining, $fixedLeft);
                    $it['discount'] += $take;
                    $fixedLeft -= $take;
                }
            }
            unset($it);
        }
        return $items;
    }

    public static function createInvoice(array $student, string $title, ?string $month, array $items, string $issueDate, string $dueDate, ?string $remarks = null): int
    {
        if (!$items) {
            throw new RuntimeException('No fee items to invoice.');
        }
        return DB::tx(function () use ($student, $title, $month, $items, $issueDate, $dueDate, $remarks) {
            $gross = array_sum(array_column($items, 'amount'));
            $disc = array_sum(array_column($items, 'discount'));
            $id = DB::insert('fee_invoices', [
                'invoice_no' => next_number('invoice'), 'student_id' => $student['id'], 'session_id' => $student['session_id'] ?? session_id_current(),
                'class_id' => $student['class_id'], 'fee_month' => $month, 'title' => $title, 'issue_date' => $issueDate, 'due_date' => $dueDate,
                'gross_amount' => $gross, 'discount' => $disc, 'fine' => 0, 'net_amount' => max(0, $gross - $disc), 'paid_amount' => 0,
                'status' => $gross - $disc <= 0 ? 'paid' : 'unpaid', 'remarks' => $remarks, 'created_by' => Auth::id(),
            ]);
            foreach ($items as $it) {
                DB::insert('fee_invoice_items', ['invoice_id' => $id, 'fee_category_id' => $it['fee_category_id'], 'description' => mb_substr($it['description'], 0, 150), 'amount' => $it['amount'], 'discount' => $it['discount']]);
            }
            return $id;
        });
    }

    /** Generate monthly invoices for active students of given classes. Returns [created, skipped, empty]. */
    public static function generateMonthly(string $month, array $classIds, array $extraCats, string $issueDate, string $dueDate): array
    {
        $sid = session_id_current();
        $monthlyCats = array_map('intval', DB::col("SELECT id FROM fee_categories WHERE frequency='monthly'"));
        $cats = array_values(array_unique(array_merge($monthlyCats, array_map('intval', $extraCats))));
        $created = $skipped = $empty = 0;
        $students = DB::all("SELECT * FROM students WHERE status='active' AND class_id IN " . DB::in($classIds), $classIds);
        foreach ($students as $s) {
            if (DB::val('SELECT id FROM fee_invoices WHERE student_id=? AND fee_month=?', [$s['id'], $month])) {
                $skipped++;
                continue;
            }
            $items = self::buildItems($s, $sid, $cats);
            if (!$items) {
                $empty++;
                continue;
            }
            self::createInvoice($s, 'Fee for ' . fmt_month($month), $month, $items, $issueDate, $dueDate);
            $created++;
        }
        audit('generate', 'fees', null, "Generated $created invoices for $month (skipped $skipped existing, $empty without fee structure)");
        return [$created, $skipped, $empty];
    }

    public static function balance(array $inv): float
    {
        return round((float)$inv['net_amount'] - (float)$inv['paid_amount'], 2);
    }

    /** Suggested late fine for an unpaid invoice according to settings */
    public static function lateFine(array $inv, ?string $onDate = null): float
    {
        $onDate ??= date('Y-m-d');
        if (in_array($inv['status'], ['paid', 'cancelled'], true) || $onDate <= $inv['due_date']) {
            return 0.0;
        }
        $amt = (float)setting('late_fine_amount', '0');
        if ($amt <= 0) {
            return 0.0;
        }
        $fine = setting('late_fine_type', 'fixed') === 'per_day' ? $amt * (day_count($inv['due_date'], $onDate) - 1) : $amt;
        $max = (float)setting('late_fine_max', '0');
        if ($max > 0) {
            $fine = min($fine, $max);
        }
        return max(0.0, round($fine - (float)$inv['fine'], 2));
    }

    public static function refreshStatus(int $invoiceId): void
    {
        DB::q("UPDATE fee_invoices SET net_amount=GREATEST(0, gross_amount-discount+fine),
               status = CASE WHEN status='cancelled' THEN 'cancelled' WHEN paid_amount >= GREATEST(0, gross_amount-discount+fine) THEN 'paid' WHEN paid_amount>0 THEN 'partial' ELSE 'unpaid' END
               WHERE id=?", [$invoiceId]);
    }

    /** Record a payment against an invoice. Returns payment id. */
    public static function pay(int $invoiceId, float $amount, float $fine, string $method, ?string $ref, string $date, ?string $remarks): int
    {
        return DB::tx(function () use ($invoiceId, $amount, $fine, $method, $ref, $date, $remarks) {
            $inv = DB::row('SELECT * FROM fee_invoices WHERE id=? FOR UPDATE', [$invoiceId]);
            if (!$inv || $inv['status'] === 'cancelled') {
                throw new ValidationException(['invoice_id' => 'Invoice not found or cancelled.']);
            }
            if ($fine < 0 || $amount <= 0) {
                throw new ValidationException(['amount' => 'Amount must be greater than zero.']);
            }
            if ($fine > 0) {
                DB::q('UPDATE fee_invoices SET fine=fine+? WHERE id=?', [$fine, $invoiceId]);
                self::refreshStatus($invoiceId);
                $inv = DB::row('SELECT * FROM fee_invoices WHERE id=?', [$invoiceId]);
            }
            $balance = self::balance($inv);
            if ($amount - $balance > 0.009) {
                throw new ValidationException(['amount' => 'Amount exceeds the outstanding balance of ' . money($balance) . '.']);
            }
            $pid = DB::insert('fee_payments', [
                'receipt_no' => next_number('receipt'), 'invoice_id' => $invoiceId, 'student_id' => $inv['student_id'], 'amount' => $amount, 'fine' => $fine,
                'method' => $method, 'reference' => $ref, 'paid_on' => $date, 'remarks' => $remarks, 'received_by' => Auth::id(),
            ]);
            DB::q('UPDATE fee_invoices SET paid_amount=paid_amount+? WHERE id=?', [$amount, $invoiceId]);
            self::refreshStatus($invoiceId);
            audit('payment', 'fees', (string)$pid, "Received " . money($amount) . " against {$inv['invoice_no']}");
            return $pid;
        });
    }
}

/* =====================================================================
 * RESULTS
 * ===================================================================== */
final class ResultService
{
    public static function gradeFor(float $pct): ?array
    {
        return DB::row('SELECT * FROM grades WHERE ? BETWEEN min_percent AND max_percent ORDER BY min_percent DESC LIMIT 1', [round($pct, 2)])
            ?? DB::row('SELECT * FROM grades WHERE min_percent<=? ORDER BY min_percent DESC LIMIT 1', [$pct]);
    }

    /** Compute & store results for one exam/class; returns number of students processed */
    public static function generate(int $examId, int $classId): int
    {
        $subjects = DB::all('SELECT * FROM exam_subjects WHERE exam_id=? AND class_id=?', [$examId, $classId]);
        if (!$subjects) {
            throw new ValidationException(['class_id' => 'No subjects are scheduled for this class in this exam.']);
        }
        $passPct = (float)setting('pass_percentage', '33');
        $eachSubject = setting('pass_each_subject', '1') === '1';
        $students = DB::all("SELECT id, section_id FROM students WHERE class_id=? AND status='active'", [$classId]);
        $esIds = array_column($subjects, 'id');
        $marks = [];
        foreach (DB::all('SELECT * FROM marks WHERE exam_subject_id IN ' . DB::in($esIds), $esIds) as $m) {
            $marks[$m['student_id']][$m['exam_subject_id']] = $m;
        }
        return DB::tx(function () use ($students, $subjects, $marks, $examId, $classId, $passPct, $eachSubject) {
            $rows = [];
            foreach ($students as $s) {
                if (empty($marks[$s['id']])) {
                    continue; // no marks entered for this student at all
                }
                $total = $obt = 0.0;
                $failed = 0;
                foreach ($subjects as $es) {
                    $m = $marks[$s['id']][$es['id']] ?? null;
                    $total += (float)$es['total_marks'];
                    $o = ($m && !$m['is_absent']) ? (float)$m['obtained_marks'] : 0.0;
                    $obt += $o;
                    if (!$m || $m['is_absent'] || $o < (float)$es['pass_marks']) {
                        $failed++;
                    }
                }
                $pct = $total > 0 ? round($obt / $total * 100, 2) : 0;
                $grade = self::gradeFor($pct);
                $pass = $pct >= $passPct && (!$eachSubject || $failed === 0) && (!$grade || $grade['is_pass']);
                $rows[] = ['exam_id' => $examId, 'student_id' => $s['id'], 'class_id' => $classId, 'section_id' => $s['section_id'], 'total_marks' => $total,
                    'obtained_marks' => $obt, 'percentage' => $pct, 'grade' => $pass ? ($grade['name'] ?? null) : (DB::val('SELECT name FROM grades WHERE is_pass=0 ORDER BY min_percent LIMIT 1') ?: 'F'),
                    'result_status' => $pass ? 'pass' : 'fail', 'subjects_failed' => $failed, 'remarks' => $pass ? ($grade['remarks'] ?? null) : 'Needs improvement', 'position' => null];
            }
            // Positions: competition ranking among passed students within the class
            $passed = array_filter($rows, fn($r) => $r['result_status'] === 'pass');
            usort($passed, fn($a, $b) => $b['obtained_marks'] <=> $a['obtained_marks']);
            $pos = 0;
            $prev = null;
            $rankMap = [];
            foreach (array_values($passed) as $i => $r) {
                if ($prev === null || $r['obtained_marks'] < $prev) {
                    $pos = $i + 1;
                    $prev = $r['obtained_marks'];
                }
                $rankMap[$r['student_id']] = $pos;
            }
            DB::q('DELETE FROM results WHERE exam_id=? AND class_id=?', [$examId, $classId]);
            foreach ($rows as $r) {
                $r['position'] = $rankMap[$r['student_id']] ?? null;
                DB::insert('results', $r);
            }
            audit('generate', 'results', (string)$examId, 'Generated results for class #' . $classId . ' (' . count($rows) . ' students)');
            return count($rows);
        });
    }

    public static function ordinal(?int $n): string
    {
        if (!$n) {
            return '-';
        }
        $s = ['th', 'st', 'nd', 'rd'];
        $v = $n % 100;
        return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
    }
}

/* =====================================================================
 * NOTICES / MESSAGING
 * ===================================================================== */
final class NoticeService
{
    public static function audienceUserIds(string $aud, ?int $classId = null): array
    {
        $q = match ($aud) {
            'all' => ["SELECT id FROM users WHERE status='active'", []],
            'students' => ["SELECT user_id FROM students WHERE status='active' AND user_id IS NOT NULL", []],
            'parents' => ["SELECT DISTINCT p.user_id FROM parents p JOIN students s ON s.parent_id=p.id WHERE s.status='active' AND p.user_id IS NOT NULL", []],
            'teachers' => ["SELECT user_id FROM teachers WHERE status='active' AND user_id IS NOT NULL", []],
            'staff' => ["SELECT user_id FROM staff WHERE status='active' AND user_id IS NOT NULL", []],
            'class' => ["SELECT s.user_id FROM students s WHERE s.class_id=? AND s.status='active' AND s.user_id IS NOT NULL
                         UNION SELECT p.user_id FROM parents p JOIN students s ON s.parent_id=p.id WHERE s.class_id=? AND s.status='active' AND p.user_id IS NOT NULL
                         UNION SELECT t.user_id FROM teachers t JOIN sections sec ON sec.class_teacher_id=t.id WHERE sec.class_id=? AND t.user_id IS NOT NULL", [$classId, $classId, $classId]],
            default => ['SELECT NULL FROM DUAL WHERE 0', []],
        };
        return array_map('intval', array_filter(DB::col($q[0], $q[1])));
    }

    /** SMS + email to parents of active students (optionally one class). Returns number of messages attempted. */
    public static function messageParents(string $aud, ?int $classId, string $sms, string $subject): int
    {
        if (!in_array($aud, ['all', 'parents', 'students', 'class'], true)) {
            return 0;
        }
        $sql = "SELECT DISTINCT p.father_phone, p.email FROM parents p JOIN students s ON s.parent_id=p.id WHERE s.status='active'";
        $params = [];
        if ($aud === 'class' && $classId) {
            $sql .= ' AND s.class_id=?';
            $params[] = $classId;
        }
        $n = 0;
        foreach (DB::all($sql, $params) as $p) {
            if ($p['father_phone']) {
                send_sms($p['father_phone'], $sms);
                $n++;
            }
            if ($p['email']) {
                send_email($p['email'], $subject, $sms);
                $n++;
            }
        }
        return $n;
    }
}

/* =====================================================================
 * PORTAL ACCOUNTS
 * ===================================================================== */
final class AccountService
{
    public static function randomPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $p = '';
        for ($i = 0; $i < 6; $i++) {
            $p .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $p . random_int(10, 99);
    }

    private static function uniqueUsername(string $base): string
    {
        $base = strtolower(preg_replace('/[^A-Za-z0-9_.]/', '', $base)) ?: 'user';
        $u = $base;
        $i = 1;
        while (DB::val('SELECT COUNT(*) FROM users WHERE username=?', [$u])) {
            $u = $base . $i++;
        }
        return $u;
    }

    /** Create or reset a login linked to a record. Returns [username, password]. */
    public static function ensureLogin(string $table, int $id, string $roleSlug, string $name, string $usernameBase, ?string $email = null, ?string $phone = null): array
    {
        $rec = DB::row("SELECT user_id FROM `$table` WHERE id=?", [$id]);
        $pw = self::randomPassword();
        $roleId = (int)DB::val('SELECT id FROM roles WHERE slug=?', [$roleSlug]);
        if (!$roleId) {
            throw new RuntimeException("Role $roleSlug missing");
        }
        if ($rec && $rec['user_id'] && ($u = DB::row('SELECT * FROM users WHERE id=?', [$rec['user_id']]))) {
            DB::update('users', ['password' => password_hash($pw, PASSWORD_DEFAULT), 'status' => 'active', 'name' => $name], 'id=?', [$u['id']]);
            audit('reset_login', $table, (string)$id, 'Portal password reset for ' . $u['username']);
            return [$u['username'], $pw];
        }
        if ($email && DB::val('SELECT COUNT(*) FROM users WHERE email=?', [$email])) {
            $email = null;
        }
        $username = self::uniqueUsername($usernameBase);
        $uid = DB::insert('users', ['role_id' => $roleId, 'name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone, 'password' => password_hash($pw, PASSWORD_DEFAULT), 'status' => 'active']);
        DB::update($table, ['user_id' => $uid], 'id=?', [$id]);
        audit('create_login', $table, (string)$id, 'Portal login created: ' . $username);
        return [$username, $pw];
    }
}
