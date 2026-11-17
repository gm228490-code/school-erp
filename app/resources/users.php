<?php
return [
    'table' => 'users', 'title' => 'User Accounts', 'singular' => 'User', 'icon' => 'bi-person-lock', 'group' => 'Administration',
    'perm' => ['view' => 'users.manage', 'manage' => 'users.manage'],
    'from' => 'users t JOIN roles r ON r.id=t.role_id', 'select' => 't.*, r.name AS role_name, r.slug AS role_slug',
    'order' => 't.name', 'search' => ['t.name', 't.username', 't.email', 't.phone'],
    // Only a Super Admin may see / manage other Super Admin accounts
    'scope' => fn() => Auth::isSuper() ? ['', []] : ["r.slug<>'super_admin'", []],
    'filters' => [
        'role' => ['label' => 'Role', 'sql' => 't.role_id', 'options' => fn() => DB::pairs('SELECT id,name FROM roles ORDER BY id')],
        'status' => ['label' => 'Status', 'sql' => 't.status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
    ],
    'columns' => [
        'photo' => ['', 'type' => 'image', 'sort' => false],
        'name' => ['Name', 'render' => fn($r) => '<div class="fw-semibold">' . e($r['name']) . '</div><div class="small text-muted">@' . e($r['username']) . '</div>'],
        'email' => ['Email'], 'phone' => ['Phone'], 'role_name' => ['Role', 'sort' => 'r.name', 'render' => fn($r) => '<span class="badge text-bg-primary badge-soft">' . e(t($r['role_name'])) . '</span>'],
        'last_login' => ['Last Login', 'render' => fn($r) => e(fmt_date($r['last_login'], 'd-M-Y h:i A'))], 'status' => ['Status', 'type' => 'badge'],
    ],
    'fields' => [
        'name' => ['label' => 'Full Name', 'rules' => 'required|max:120'],
        'username' => ['label' => 'Username', 'rules' => 'required|min:4|max:60', 'unique' => true],
        'email' => ['label' => 'Email', 'type' => 'email', 'unique' => true],
        'phone' => ['label' => 'Phone', 'rules' => 'phone'],
        'role_id' => ['label' => 'Role', 'type' => 'select', 'rules' => 'required',
            'options' => fn() => Auth::isSuper() ? DB::pairs('SELECT id,name FROM roles ORDER BY id') : DB::pairs("SELECT id,name FROM roles WHERE slug<>'super_admin' ORDER BY id")],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'rules' => 'required', 'default' => 'active'],
        'new_password' => ['label' => 'Password', 'type' => 'password', 'help' => 'Required for new users. Leave blank to keep the current password. Min 8 characters with letters and numbers.', 'attrs' => 'autocomplete="new-password"'],
        'photo' => ['label' => 'Photo', 'type' => 'image', 'dir' => 'users'],
    ],
    'before_save' => function ($d, $id, $old) {
        $pw = (string)($d['new_password'] ?? '');
        unset($d['new_password']);
        if (!$id && $pw === '') {
            throw new ValidationException(['new_password' => 'Password is required for new users.']);
        }
        if ($pw !== '') {
            if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
                throw new ValidationException(['new_password' => 'Password must be at least 8 characters with letters and numbers.']);
            }
            $d['password'] = password_hash($pw, PASSWORD_DEFAULT);
            $d['password_changed_at'] = date('Y-m-d H:i:s');
        }
        if ($id && $id === Auth::id() && ($d['status'] !== 'active' || (int)$d['role_id'] !== (int)Auth::user()['role_id'])) {
            throw new ValidationException(['status' => 'You cannot deactivate or change the role of your own account.']);
        }
        $superRole = (int)DB::val("SELECT id FROM roles WHERE slug='super_admin'");
        if ($id && $old && $old['role_slug'] === 'super_admin' && ((int)$d['role_id'] !== $superRole || $d['status'] !== 'active')
            && (int)DB::val("SELECT COUNT(*) FROM users WHERE role_id=? AND status='active' AND id<>?", [$superRole, $id]) === 0) {
            throw new ValidationException(['role_id' => 'At least one active Super Admin must remain.']);
        }
        return $d;
    },
    'deletable' => fn($r) => (int)$r['id'] !== Auth::id(),
    'before_delete' => function ($id, $r) {
        if ((int)$id === Auth::id()) {
            return 'You cannot delete your own account.';
        }
        if ($r['role_slug'] === 'super_admin' && (int)DB::val("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.slug='super_admin'") <= 1) {
            return 'The last Super Admin cannot be deleted.';
        }
        return null;
    },
];
