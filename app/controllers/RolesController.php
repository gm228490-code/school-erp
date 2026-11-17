<?php
declare(strict_types=1);

final class RolesController
{
    public function index(): void
    {
        authorize('roles.manage');
        $roles = DB::all('SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id=r.id) AS users, (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id=r.id) AS perms FROM roles r ORDER BY r.id');
        $roleId = input_int('role_id') ?? (int)($roles[1]['id'] ?? $roles[0]['id']);
        $role = DB::row('SELECT * FROM roles WHERE id=?', [$roleId]);
        $perms = DB::all('SELECT * FROM permissions ORDER BY id');
        $granted = array_flip(array_map('intval', DB::col('SELECT permission_id FROM role_permissions WHERE role_id=?', [$roleId])));
        view('roles/index', compact('roles', 'role', 'perms', 'granted') + ['title' => t('Roles & Permissions')]);
    }

    public function save(): void
    {
        authorize('roles.manage');
        $role = DB::row('SELECT * FROM roles WHERE id=?', [input_int('role_id', 0)]);
        if (!$role) {
            abort(404);
        }
        if ($role['slug'] === 'super_admin') {
            throw new ValidationException(['perms' => 'Super Admin always has full access.']);
        }
        $valid = array_map('intval', DB::col('SELECT id FROM permissions'));
        $sel = array_values(array_intersect(array_map('intval', (array)($_POST['perms'] ?? [])), $valid));
        DB::tx(function () use ($role, $sel) {
            DB::delete('role_permissions', 'role_id=?', [$role['id']]);
            foreach ($sel as $pid) {
                DB::insert('role_permissions', ['role_id' => $role['id'], 'permission_id' => $pid]);
            }
        });
        audit('permissions', 'roles', (string)$role['id'], $role['name'] . ': ' . count($sel) . ' permissions');
        flash('success', sprintf(t('Permissions updated for %s.'), $role['name']));
        redirect(url('roles', ['role_id' => $role['id']]));
    }

    public function create(): void
    {
        authorize('roles.manage');
        $name = trim((string)input('name'));
        validate_or_fail(['name' => $name], ['name' => 'required|max:60']);
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $name), '_'));
        if (!$slug || DB::val('SELECT id FROM roles WHERE slug=?', [$slug])) {
            throw new ValidationException(['name' => 'A role with this name already exists.']);
        }
        $id = DB::insert('roles', ['name' => $name, 'slug' => $slug, 'description' => mb_substr((string)input('description', ''), 0, 255) ?: null, 'is_system' => 0]);
        if ($copy = input_int('copy_from')) {
            DB::q('INSERT INTO role_permissions (role_id, permission_id) SELECT ?, permission_id FROM role_permissions WHERE role_id=?', [$id, $copy]);
        }
        audit('create', 'roles', (string)$id, $name);
        flash('success', t('Role created.'));
        redirect(url('roles', ['role_id' => $id]));
    }

    public function delete(): void
    {
        authorize('roles.manage');
        $role = DB::row('SELECT * FROM roles WHERE id=?', [input_int('id', 0)]);
        if (!$role || $role['is_system']) {
            throw new HttpException(t('Built-in roles cannot be deleted.'), 400);
        }
        if (DB::val('SELECT COUNT(*) FROM users WHERE role_id=?', [$role['id']])) {
            throw new HttpException(t('Reassign the users of this role first.'), 400);
        }
        DB::delete('roles', 'id=?', [$role['id']]);
        audit('delete', 'roles', (string)$role['id'], $role['name']);
        json_out(['ok' => true, 'message' => t('Role deleted.'), 'redirect' => url('roles')]);
    }
}
