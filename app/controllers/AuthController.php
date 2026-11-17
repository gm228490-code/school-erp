<?php
declare(strict_types=1);

final class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            redirect(Auth::isPortal() ? url('portal') : url('dashboard'));
        }
        $error = '';
        if (is_post()) {
            $login = (string)input('login', '');
            $pass = (string)($_POST['password'] ?? '');
            if ($login === '' || $pass === '') {
                $error = t('Please enter your username and password.');
            } else {
                [$ok, $msg] = Auth::attempt($login, $pass);
                if ($ok) {
                    $intended = $_SESSION['intended'] ?? '';
                    unset($_SESSION['intended']);
                    if (Auth::isPortal()) {
                        redirect(url('portal'));
                    }
                    redirect($intended && str_starts_with($intended, base_url() . '/index.php') ? $intended : url('dashboard'));
                }
                $error = $msg;
            }
        }
        view('auth/login', ['error' => $error, 'title' => t('Login')], 'blank');
    }

    public function logout(): void
    {
        if (is_post()) {
            Auth::logout();
            session_start();
            flash('success', t('You have been logged out securely.'));
        }
        redirect(url('auth/login'));
    }

    public function profile(): void
    {
        $u = Auth::user();
        if (is_post()) {
            $action = input('action');
            if ($action === 'password') {
                $cur = (string)($_POST['current_password'] ?? '');
                $new = (string)($_POST['password'] ?? '');
                $hash = (string)DB::val('SELECT password FROM users WHERE id=?', [$u['id']]);
                if (!password_verify($cur, $hash)) {
                    throw new ValidationException(['current_password' => 'Current password is incorrect.']);
                }
                validate_or_fail($_POST, ['password' => 'required|min:8|confirmed'], ['password' => 'New password']);
                if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
                    throw new ValidationException(['password' => 'Password must contain letters and numbers.']);
                }
                DB::update('users', ['password' => password_hash($new, PASSWORD_DEFAULT), 'password_changed_at' => date('Y-m-d H:i:s')], 'id=?', [$u['id']]);
                session_regenerate_id(true);
                audit('password_change', 'auth', (string)$u['id']);
                flash('success', t('Password changed successfully.'));
            } else {
                $data = ['name' => input('name'), 'email' => input('email'), 'phone' => input('phone')];
                validate_or_fail($data, ['name' => 'required|max:120', 'email' => 'email|unique:users,email,' . $u['id'], 'phone' => 'phone']);
                $data = nullify($data);
                if ($photo = upload_file('photo', 'users', 'image')) {
                    $data['photo'] = $photo;
                    delete_upload($u['photo']);
                }
                DB::update('users', $data, 'id=?', [$u['id']]);
                audit('profile_update', 'auth', (string)$u['id']);
                flash('success', t('Profile updated.'));
            }
            redirect(url('auth/profile'));
        }
        $logins = DB::all("SELECT action, ip, created_at FROM audit_logs WHERE user_id=? AND module='auth' ORDER BY id DESC LIMIT 10", [$u['id']]);
        view('auth/profile', ['u' => DB::row('SELECT * FROM users WHERE id=?', [$u['id']]), 'logins' => $logins, 'title' => t('My Profile')], Auth::isPortal() ? 'portal' : 'main');
    }
}
