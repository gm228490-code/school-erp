<?php
declare(strict_types=1);

final class ValidationException extends RuntimeException
{
    public function __construct(public array $errors, string $message = 'Please correct the highlighted errors.')
    {
        parent::__construct($message);
    }
}

final class HttpException extends RuntimeException
{
}

/* =========================================================
 * Request / response
 * ========================================================= */
function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    static $b = null;
    if ($b === null) {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if (str_ends_with($b, '/install')) {
            $b = substr($b, 0, -8);
        }
    }
    return $b;
}

function url(string $route = 'dashboard', array $params = []): string
{
    $q = http_build_query(array_merge(['r' => $route], $params));
    return base_url() . '/index.php?' . $q;
}

function asset(string $path): string
{
    $file = BASE_PATH . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return base_url() . '/assets/' . $path . '?v=' . $v;
}

function upload_url(?string $path): string
{
    return $path ? base_url() . '/index.php?r=file/show&p=' . rawurlencode($path) : '';
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    redirect($ref && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST) ? $ref : url('dashboard'));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function input(string $key, mixed $default = null): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, ?int $default = null): ?int
{
    $v = input($key);
    return ($v === null || $v === '' || !is_numeric($v)) ? $default : (int)$v;
}

function input_date(string $key, ?string $default = null): ?string
{
    $v = (string)input($key, '');
    return valid_date($v) ? $v : $default;
}

function valid_date(?string $d): bool
{
    if (!$d || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return false;
    }
    [$y, $m, $dd] = array_map('intval', explode('-', $d));
    return checkdate($m, $dd, $y);
}

function valid_month(?string $m): bool
{
    return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$m);
}

function nullify(array $data): array
{
    foreach ($data as $k => $v) {
        if ($v === '') {
            $data[$k] = null;
        }
    }
    return $data;
}

function abort(int $code, string $message = ''): never
{
    throw new HttpException($message ?: ($code === 403 ? t('You do not have permission to access this page.') : t('Page not found.')), $code);
}

/* =========================================================
 * Session flash & old input
 * ========================================================= */
function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = [$type, $msg];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function keep_old(): void
{
    $data = $_POST;
    unset($data['_token'], $data['password'], $data['password_confirmation']);
    $_SESSION['_old'] = $data;
}

function old(string $key, mixed $default = ''): mixed
{
    static $old = null;
    if ($old === null) {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }
    return $old[$key] ?? $default;
}

/* =========================================================
 * CSRF
 * ========================================================= */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        if (is_ajax()) {
            json_out(['ok' => false, 'message' => 'Session expired. Please reload the page.'], 419);
        }
        http_response_code(419);
        flash('danger', 'Your session expired. Please try again.');
        back();
    }
}

/* =========================================================
 * Authorization
 * ========================================================= */
function authorize(string ...$perms): void
{
    if (!Auth::canAny(...$perms)) {
        abort(403);
    }
}

function can(string $perm): bool
{
    return Auth::can($perm);
}

/* =========================================================
 * Settings / i18n / formatting
 * ========================================================= */
function settings(): array
{
    static $s = null;
    if ($s === null) {
        try {
            $s = DB::pairs('SELECT `key`,`value` FROM settings');
        } catch (Throwable) {
            $s = [];
        }
    }
    return $s;
}

function setting(string $key, mixed $default = ''): mixed
{
    $v = settings()[$key] ?? null;
    return ($v === null || $v === '') ? $default : $v;
}

function save_setting(string $key, ?string $value): void
{
    DB::upsert('settings', ['key' => $key, 'value' => $value], ['value']);
}

function lang(): string
{
    return in_array($_SESSION['lang'] ?? '', ['en', 'ur'], true) ? $_SESSION['lang'] : (setting('default_lang', 'en'));
}

function is_rtl(): bool
{
    return lang() === 'ur';
}

function t(string $text): string
{
    static $dict = null;
    if (lang() === 'en') {
        return $text;
    }
    if ($dict === null) {
        $f = BASE_PATH . '/lang/' . lang() . '.php';
        $dict = is_file($f) ? require $f : [];
    }
    return $dict[$text] ?? $text;
}

function money(mixed $n, bool $symbol = true): string
{
    $dec = (int)setting('currency_decimals', '0');
    $s = number_format((float)$n, $dec);
    return $symbol ? setting('currency_symbol', 'Rs.') . ' ' . $s : $s;
}

function fmt_date(?string $d, ?string $fmt = null): string
{
    if (!$d || str_starts_with($d, '0000')) {
        return '';
    }
    $ts = strtotime($d);
    return $ts ? date($fmt ?? setting('date_format', 'd-M-Y'), $ts) : '';
}

function fmt_month(?string $ym): string
{
    return $ym ? date('F Y', strtotime($ym . '-01')) : '';
}

function fmt_time(?string $t): string
{
    return $t ? date('h:i A', strtotime($t)) : '';
}

function status_badge(?string $status): string
{
    $map = [
        'active' => 'success', 'paid' => 'success', 'present' => 'success', 'approved' => 'success', 'pass' => 'success', 'returned' => 'success', 'reviewed' => 'success', 'submitted' => 'info',
        'inactive' => 'secondary', 'cancelled' => 'secondary', 'closed' => 'secondary', 'graduated' => 'info', 'transferred' => 'info',
        'unpaid' => 'danger', 'absent' => 'danger', 'rejected' => 'danger', 'fail' => 'danger', 'withdrawn' => 'danger', 'lost' => 'danger', 'resigned' => 'secondary',
        'partial' => 'warning', 'late' => 'warning', 'leave' => 'info', 'pending' => 'warning', 'suspended' => 'warning', 'issued' => 'primary', 'maintenance' => 'warning',
    ];
    $c = $map[strtolower((string)$status)] ?? 'secondary';
    return '<span class="badge rounded-pill text-bg-' . $c . ' badge-soft">' . e(t(ucfirst(str_replace('_', ' ', (string)$status)))) . '</span>';
}

function months_list(): array
{
    $m = [];
    for ($i = 1; $i <= 12; $i++) {
        $m[$i] = date('F', mktime(0, 0, 0, $i, 1));
    }
    return $m;
}

function days_of_week(): array
{
    return [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
}

function school_days(): array
{
    $d = array_filter(array_map('intval', explode(',', (string)setting('working_days', '1,2,3,4,5,6'))));
    return $d ?: [1, 2, 3, 4, 5, 6];
}

/* =========================================================
 * Views
 * ========================================================= */
function view(string $name, array $data = [], ?string $layout = 'main'): never
{
    echo render($name, $data, $layout);
    exit;
}

function render(string $name, array $data = [], ?string $layout = 'main'): string
{
    $file = APP_PATH . '/views/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: $name");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $file;
    $content = ob_get_clean();
    if ($layout === null) {
        return $content;
    }
    ob_start();
    include APP_PATH . '/views/layouts/' . $layout . '.php';
    return ob_get_clean();
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    include APP_PATH . '/views/partials/' . $name . '.php';
}

/** <option> list builder */
function options(array $items, mixed $selected = null, ?string $placeholder = null): string
{
    $h = $placeholder !== null ? '<option value="">' . e($placeholder) . '</option>' : '';
    $sel = is_array($selected) ? array_map('strval', $selected) : [(string)$selected];
    foreach ($items as $k => $v) {
        $h .= '<option value="' . e($k) . '"' . (in_array((string)$k, $sel, true) ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    return $h;
}

/* =========================================================
 * Lookups
 * ========================================================= */
function current_session(): ?array
{
    static $s = false;
    if ($s === false) {
        $sel = (int)($_SESSION['session_id'] ?? 0);
        $s = ($sel ? DB::row('SELECT * FROM academic_sessions WHERE id=?', [$sel]) : null)
            ?? DB::row('SELECT * FROM academic_sessions WHERE is_current=1 LIMIT 1')
            ?? DB::row('SELECT * FROM academic_sessions ORDER BY start_date DESC LIMIT 1');
    }
    return $s;
}

function session_id_current(): int
{
    return (int)(current_session()['id'] ?? 0);
}

function class_options(): array
{
    if (Auth::scoped()) {
        $ids = Auth::teacherSectionIds();
        return DB::pairs('SELECT DISTINCT c.id, c.name FROM classes c JOIN sections s ON s.class_id=c.id WHERE s.id IN ' . DB::in($ids) . ' ORDER BY c.numeric_order, c.name', $ids);
    }
    return DB::pairs('SELECT id, name FROM classes ORDER BY numeric_order, name');
}

function section_options(?int $classId = null): array
{
    $sql = 'SELECT s.id, CONCAT(c.name, " - ", s.name) FROM sections s JOIN classes c ON c.id=s.class_id WHERE 1=1';
    $p = [];
    if ($classId) {
        $sql = 'SELECT s.id, s.name FROM sections s WHERE s.class_id=?';
        $p[] = $classId;
    }
    if (Auth::scoped()) {
        $ids = Auth::teacherSectionIds();
        $sql .= ' AND s.id IN ' . DB::in($ids);
        $p = array_merge($p, $ids);
    }
    return DB::pairs($sql . ' ORDER BY s.name', $p);
}

function subject_options(?int $classId = null): array
{
    if ($classId) {
        $r = DB::pairs('SELECT DISTINCT sb.id, sb.name FROM class_subjects cs JOIN subjects sb ON sb.id=cs.subject_id WHERE cs.class_id=? ORDER BY sb.name', [$classId]);
        if ($r) {
            return $r;
        }
    }
    return DB::pairs('SELECT id, name FROM subjects ORDER BY name');
}

function teacher_options(): array
{
    return DB::pairs("SELECT id, CONCAT(name,' (',employee_no,')') FROM teachers WHERE status='active' ORDER BY name");
}

function session_options(): array
{
    return DB::pairs('SELECT id, name FROM academic_sessions ORDER BY start_date DESC');
}

/** Combined teacher+staff options keyed "teacher:ID" / "staff:ID" */
function employee_options(bool $activeOnly = true): array
{
    $w = $activeOnly ? " WHERE status='active'" : '';
    $o = [];
    foreach (DB::all("SELECT id, name, employee_no FROM teachers$w ORDER BY name") as $r) {
        $o['teacher:' . $r['id']] = $r['name'] . ' (' . $r['employee_no'] . ') - ' . t('Teacher');
    }
    foreach (DB::all("SELECT id, name, employee_no FROM staff$w ORDER BY name") as $r) {
        $o['staff:' . $r['id']] = $r['name'] . ' (' . $r['employee_no'] . ') - ' . t('Staff');
    }
    return $o;
}

function student_options(): array
{
    return DB::pairs("SELECT s.id, CONCAT(s.first_name,' ',IFNULL(s.last_name,''),' (',s.reg_no,') - ',c.name) FROM students s JOIN classes c ON c.id=s.class_id WHERE s.status='active' ORDER BY s.first_name");
}

/** SQL expression that resolves emp_type/emp_id to a name */
function employee_name_sql(string $alias = 't'): string
{
    return "CASE $alias.emp_type WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=$alias.emp_id) ELSE (SELECT name FROM staff WHERE id=$alias.emp_id) END";
}

function split_employee(array $d): array
{
    if (!preg_match('/^(teacher|staff):(\d+)$/', (string)($d['employee'] ?? ''), $m)) {
        throw new ValidationException(['employee' => 'Please select an employee.']);
    }
    unset($d['employee']);
    $d['emp_type'] = $m[1];
    $d['emp_id'] = (int)$m[2];
    return $d;
}

function student_name(array $s): string
{
    return trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
}

function employee(string $type, int $id): ?array
{
    $table = $type === 'teacher' ? 'teachers' : 'staff';
    return DB::row("SELECT * FROM $table WHERE id=?", [$id]);
}

/* =========================================================
 * Numbering (atomic sequences)
 * ========================================================= */
function next_number(string $name): string
{
    $prefixMap = [
        'student' => setting('reg_prefix', 'STD-'),
        'receipt' => setting('receipt_prefix', 'RCP-'),
        'invoice' => setting('invoice_prefix', 'INV-'),
        'teacher' => setting('teacher_prefix', 'TCH-'),
        'staff'   => setting('staff_prefix', 'STF-'),
        'salary'  => 'SAL-',
    ];
    return DB::tx(function () use ($name, $prefixMap) {
        DB::q('INSERT IGNORE INTO sequences (name, next_value) VALUES (?, 1)', [$name]);
        $n = (int)DB::val('SELECT next_value FROM sequences WHERE name=? FOR UPDATE', [$name]);
        DB::q('UPDATE sequences SET next_value=next_value+1 WHERE name=?', [$name]);
        $year = in_array($name, ['student', 'receipt', 'invoice', 'salary'], true) ? date('y') : '';
        return ($prefixMap[$name] ?? strtoupper($name) . '-') . $year . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
    });
}

/* =========================================================
 * Audit & notifications
 * ========================================================= */
function audit(string $action, string $module, ?string $recordId = null, ?string $details = null, ?int $userId = null): void
{
    try {
        DB::insert('audit_logs', [
            'user_id' => $userId ?? Auth::id(),
            'action' => mb_substr($action, 0, 50),
            'module' => mb_substr($module, 0, 50),
            'record_id' => $recordId,
            'details' => $details ? mb_substr($details, 0, 2000) : null,
            'ip' => client_ip(),
            'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('Audit failure: ' . $e->getMessage());
    }
}

function notify(array $userIds, string $title, string $message = '', string $link = '', string $type = 'general'): int
{
    $userIds = array_unique(array_filter(array_map('intval', $userIds)));
    if (!$userIds) {
        return 0;
    }
    $st = DB::pdo()->prepare('INSERT INTO notifications (user_id,title,message,link,type) VALUES (?,?,?,?,?)');
    foreach ($userIds as $uid) {
        $st->execute([$uid, mb_substr($title, 0, 200), mb_substr($message, 0, 1000), $link ?: null, $type]);
    }
    return count($userIds);
}

/** Send email via PHP mail() if enabled in settings; logs the outcome */
function send_email(string $to, string $subject, string $body): bool
{
    if (setting('email_enabled', '0') !== '1' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $from = setting('email_from', setting('school_email', 'noreply@localhost'));
    $headers = "From: " . setting('school_name', 'School') . " <$from>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    DB::insert('message_logs', ['channel' => 'email', 'recipient' => $to, 'subject' => $subject, 'message' => $body, 'status' => $ok ? 'sent' : 'failed']);
    return $ok;
}

/**
 * Send SMS through an HTTP gateway configured in settings.
 * sms_api_url may contain placeholders {to} and {message}; e.g.
 * https://api.gateway.pk/send?key=XXX&to={to}&text={message}
 */
function send_sms(string $to, string $message): bool
{
    $tpl = (string)setting('sms_api_url', '');
    if (setting('sms_enabled', '0') !== '1' || $tpl === '' || !preg_match('/^\+?\d{10,15}$/', preg_replace('/[\s-]/', '', $to))) {
        return false;
    }
    $to = preg_replace('/[\s-]/', '', $to);
    $endpoint = str_replace(['{to}', '{message}'], [rawurlencode($to), rawurlencode($message)], $tpl);
    $resp = '';
    $ok = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $resp = (string)curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ok = $code >= 200 && $code < 300;
    }
    DB::insert('message_logs', ['channel' => 'sms', 'recipient' => $to, 'message' => $message, 'status' => $ok ? 'sent' : 'failed', 'response' => mb_substr($resp, 0, 1000)]);
    return $ok;
}

/* =========================================================
 * File uploads (validated by real MIME type, random names, outside web root access via controller)
 * ========================================================= */
function upload_file(string $field, string $dir, string $kind = 'image', bool $required = false): ?string
{
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new ValidationException([$field => 'File is required.']);
        }
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new ValidationException([$field => 'Upload failed (error ' . $f['error'] . ').']);
    }
    $maxMb = (int)setting('max_upload_mb', '5');
    if ($f['size'] > $maxMb * 1024 * 1024) {
        throw new ValidationException([$field => "File exceeds {$maxMb} MB."]);
    }
    $allowed = [
        'image' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'],
        'doc'   => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf',
                    'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                    'text/plain' => 'txt', 'application/zip' => 'zip'],
    ][$kind];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new ValidationException([$field => 'File type not allowed (' . $mime . ').']);
    }
    if ($kind === 'image' && @getimagesize($f['tmp_name']) === false) {
        throw new ValidationException([$field => 'Invalid image file.']);
    }
    $dir = preg_replace('/[^a-z0-9_]/', '', strtolower($dir));
    $target = UPLOAD_PATH . '/' . $dir . '/' . date('Y/m');
    if (!is_dir($target) && !mkdir($target, 0755, true)) {
        throw new RuntimeException('Cannot create upload directory');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $target . '/' . $name)) {
        throw new RuntimeException('Cannot store uploaded file');
    }
    return $dir . '/' . date('Y/m') . '/' . $name;
}

function delete_upload(?string $path): void
{
    if ($path && !str_contains($path, '..')) {
        $full = UPLOAD_PATH . '/' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

/* =========================================================
 * Validation
 * Rules: required|email|numeric|integer|date|month|min:x|max:x|in:a,b|unique:table,col[,ignoreId]|exists:table,col|phone|cnic|confirmed
 * ========================================================= */
function validate(array $data, array $rules, array $labels = []): array
{
    $errors = [];
    foreach ($rules as $field => $ruleStr) {
        $v = $data[$field] ?? null;
        $v = is_string($v) ? trim($v) : $v;
        $label = $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
        $rulesArr = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
        $isNumeric = in_array('numeric', $rulesArr, true) || in_array('integer', $rulesArr, true);
        foreach ($rulesArr as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $empty = $v === null || $v === '' || $v === [];
            if ($name === 'required') {
                if ($empty) {
                    $errors[$field] = "$label is required.";
                    break;
                }
                continue;
            }
            if ($empty) {
                continue;
            }
            $msg = match ($name) {
                'email' => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email.",
                'numeric' => is_numeric($v) ? null : "$label must be a number.",
                'integer' => filter_var($v, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
                'date' => valid_date((string)$v) ? null : "$label must be a valid date.",
                'month' => valid_month((string)$v) ? null : "$label must be a valid month.",
                'time' => preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string)$v) ? null : "$label must be a valid time.",
                'min' => $isNumeric ? ((float)$v >= (float)$arg ? null : "$label must be at least $arg.") : (mb_strlen((string)$v) >= (int)$arg ? null : "$label must be at least $arg characters."),
                'max' => $isNumeric ? ((float)$v <= (float)$arg ? null : "$label may not exceed $arg.") : (mb_strlen((string)$v) <= (int)$arg ? null : "$label may not exceed $arg characters."),
                'in' => in_array((string)$v, explode(',', (string)$arg), true) ? null : "$label is invalid.",
                'phone' => preg_match('/^[0-9+\-\s()]{7,20}$/', (string)$v) ? null : "$label must be a valid phone number.",
                'cnic' => preg_match('/^\d{5}-?\d{7}-?\d$/', (string)$v) ? null : "$label must be a valid CNIC (e.g. 35202-1234567-1).",
                'confirmed' => ($v === ($data[$field . '_confirmation'] ?? null)) ? null : "$label confirmation does not match.",
                'unique' => (function () use ($arg, $v, $label) {
                    $p = explode(',', (string)$arg);
                    $t = preg_replace('/\W/', '', $p[0]);
                    $c = preg_replace('/\W/', '', $p[1]);
                    $sql = "SELECT COUNT(*) FROM `$t` WHERE `$c`=?";
                    $params = [$v];
                    if (!empty($p[2])) {
                        $sql .= ' AND id<>?';
                        $params[] = (int)$p[2];
                    }
                    return (int)DB::val($sql, $params) > 0 ? "$label already exists." : null;
                })(),
                'exists' => (function () use ($arg, $v, $label) {
                    $p = explode(',', (string)$arg);
                    $t = preg_replace('/\W/', '', $p[0]);
                    $c = preg_replace('/\W/', '', $p[1] ?? 'id');
                    return (int)DB::val("SELECT COUNT(*) FROM `$t` WHERE `$c`=?", [$v]) ? null : "Selected $label is invalid.";
                })(),
                default => null,
            };
            if ($msg) {
                $errors[$field] = $msg;
                break;
            }
        }
    }
    return $errors;
}

function validate_or_fail(array $data, array $rules, array $labels = []): void
{
    $errors = validate($data, $rules, $labels);
    if ($errors) {
        throw new ValidationException($errors);
    }
}

/* =========================================================
 * Pagination & sorting
 * ========================================================= */
function paginate(string $countSql, string $dataSql, array $params = [], int $perPage = 20): array
{
    $perPage = max(5, min(200, input_int('per_page', $perPage) ?? $perPage));
    $total = (int)DB::val($countSql, $params);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($pages, input_int('page', 1) ?? 1));
    $offset = ($page - 1) * $perPage;
    $rows = DB::all($dataSql . " LIMIT $perPage OFFSET $offset", $params);
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'from' => $total ? $offset + 1 : 0, 'to' => $offset + count($rows)];
}

/** Current URL with modified query params */
function url_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    foreach ($q as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        }
    }
    return base_url() . '/index.php?' . http_build_query($q);
}

function sort_link(string $key, string $label): string
{
    $cur = $_GET['sort'] ?? '';
    $dir = ($_GET['dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';
    $newDir = ($cur === $key && $dir === 'asc') ? 'desc' : 'asc';
    $icon = $cur === $key ? ($dir === 'asc' ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>') : '';
    return '<a class="sort-link" href="' . e(url_with(['sort' => $key, 'dir' => $newDir, 'page' => 1])) . '">' . e($label) . $icon . '</a>';
}

/** Resolve ORDER BY safely from a whitelist */
function order_by(array $allowed, string $default): string
{
    $k = $_GET['sort'] ?? '';
    if (!isset($allowed[$k])) {
        return $default;
    }
    $dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
    return $allowed[$k] . ' ' . $dir;
}

/* =========================================================
 * Export helpers (CSV & Excel)
 * ========================================================= */
function export_table(string $filename, array $headers, array $rows, string $format = 'csv'): never
{
    $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '_' . date('Ymd_His');
    if ($format === 'xls') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        echo "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body><table border=\"1\"><tr>";
        foreach ($headers as $h) {
            echo '<th style="background:#1e40af;color:#fff">' . e($h) . '</th>';
        }
        echo '</tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($r as $c) {
                echo '<td>' . e(csv_safe((string)$c)) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, array_map(fn($c) => csv_safe((string)$c), array_values($r)));
    }
    fclose($out);
    exit;
}

/** Prevent CSV/formula injection */
function csv_safe(string $v): string
{
    return ($v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) && !is_numeric($v)) ? "'" . $v : $v;
}

function json_attr(mixed $v): string
{
    return e(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
}

/** Days between two dates inclusive */
function day_count(string $from, string $to): int
{
    return (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
}
