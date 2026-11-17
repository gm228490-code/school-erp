<?php
declare(strict_types=1);

/**
 * Authentication, session hardening, brute-force protection and RBAC.
 */
final class Auth
{
    private static ?array $user = null;
    private static ?array $perms = null;

    public const MAX_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;
    public const IDLE_TIMEOUT = 7200; // seconds

    public static function attempt(string $login, string $password): array
    {
        $ip = client_ip();
        $login = trim($login);
        $since = date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60);
        $fails = (int)DB::val('SELECT COUNT(*) FROM login_attempts WHERE success=0 AND created_at>=? AND (username=? OR ip=?)', [$since, $login, $ip]);
        if ($fails >= self::MAX_ATTEMPTS) {
            return [false, sprintf(t('Too many failed attempts. Try again in %d minutes.'), self::LOCK_MINUTES)];
        }

        $u = DB::row('SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=? OR u.email=? LIMIT 1', [$login, $login]);
        // constant-ish time: always run password_verify
        $hash = $u['password'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $ok = password_verify($password, $hash) && $u;

        DB::insert('login_attempts', ['username' => mb_substr($login, 0, 150), 'ip' => $ip, 'success' => $ok ? 1 : 0]);

        if (!$ok) {
            audit('login_failed', 'auth', null, 'Login: ' . mb_substr($login, 0, 60), null);
            return [false, t('Invalid username or password.')];
        }
        if ($u['status'] !== 'active') {
            return [false, t('Your account is inactive. Contact the administrator.')];
        }
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], 'id=?', [$u['id']]);
        }
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['uid'] = (int)$u['id'];
        $_SESSION['ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $_SESSION['last'] = time();
        $_SESSION['lang'] = $u['lang'] ?: 'en';
        DB::update('users', ['last_login' => date('Y-m-d H:i:s'), 'last_ip' => $ip], 'id=?', [$u['id']]);
        DB::q('DELETE FROM login_attempts WHERE success=0 AND (username=? OR ip=?)', [$login, $ip]);
        audit('login', 'auth', (string)$u['id'], 'Successful login', (int)$u['id']);
        return [true, ''];
    }

    public static function check(): bool
    {
        if (empty($_SESSION['uid'])) {
            return false;
        }
        if (($_SESSION['ua'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')
            || time() - (int)($_SESSION['last'] ?? 0) > self::IDLE_TIMEOUT) {
            self::logout(false);
            return false;
        }
        $_SESSION['last'] = time();
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['uid'])) {
            self::$user = DB::row("SELECT u.id,u.role_id,u.name,u.username,u.email,u.phone,u.photo,u.status,u.lang,u.theme,u.last_login,
                    r.slug AS role_slug, r.name AS role_name
                FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.status='active'", [$_SESSION['uid']]);
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): string
    {
        return self::user()['role_slug'] ?? '';
    }

    public static function isSuper(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function isPortal(): bool
    {
        return in_array(self::role(), ['parent', 'student'], true);
    }

    public static function can(string $perm): bool
    {
        if (!self::user()) {
            return false;
        }
        if (self::isSuper()) {
            return true;
        }
        if (self::$perms === null) {
            self::$perms = array_flip(DB::col('SELECT p.slug FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=?', [self::user()['role_id']]));
        }
        return isset(self::$perms[$perm]);
    }

    public static function canAny(string ...$perms): bool
    {
        foreach ($perms as $p) {
            if (self::can($p)) {
                return true;
            }
        }
        return false;
    }

    public static function logout(bool $log = true): void
    {
        if ($log && !empty($_SESSION['uid'])) {
            audit('logout', 'auth', (string)$_SESSION['uid'], 'Logged out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
        self::$perms = null;
    }

    public static function refresh(): void
    {
        self::$user = null;
        self::$perms = null;
    }

    /* ---------- Teacher scoping ---------- */
    private static ?array $teacher = null;

    public static function teacher(): ?array
    {
        if (self::$teacher === null) {
            self::$teacher = self::role() === 'teacher' ? (DB::row('SELECT * FROM teachers WHERE user_id=?', [self::id()]) ?: []) : [];
        }
        return self::$teacher ?: null;
    }

    /** true when the current user must be restricted to own classes */
    public static function scoped(): bool
    {
        return self::role() === 'teacher';
    }

    /** section ids the teacher is linked to (class teacher, subject teacher, or timetable) */
    public static function teacherSectionIds(): array
    {
        $t = self::teacher();
        if (!$t) {
            return [];
        }
        return array_map('intval', DB::col(
            'SELECT id FROM sections WHERE class_teacher_id=? UNION SELECT section_id FROM class_subjects WHERE teacher_id=? UNION SELECT section_id FROM timetables WHERE teacher_id=?',
            [$t['id'], $t['id'], $t['id']]
        ));
    }

    /** May the current user work with this section? */
    public static function canSection(?int $sectionId): bool
    {
        if (!self::scoped()) {
            return true;
        }
        return $sectionId !== null && in_array($sectionId, self::teacherSectionIds(), true);
    }

    /** Teacher may enter marks / homework for subject in section */
    public static function canSubject(int $sectionId, int $subjectId): bool
    {
        if (!self::scoped()) {
            return true;
        }
        $t = self::teacher();
        if (!$t) {
            return false;
        }
        if ((int)DB::val('SELECT COUNT(*) FROM sections WHERE id=? AND class_teacher_id=?', [$sectionId, $t['id']]) > 0) {
            return true;
        }
        return (int)DB::val('SELECT COUNT(*) FROM class_subjects WHERE section_id=? AND subject_id=? AND teacher_id=?', [$sectionId, $subjectId, $t['id']])
            + (int)DB::val('SELECT COUNT(*) FROM timetables WHERE section_id=? AND subject_id=? AND teacher_id=?', [$sectionId, $subjectId, $t['id']]) > 0;
    }
}
