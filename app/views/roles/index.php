<?php partial('page_head', ['title' => t('Roles & Permissions'), 'icon' => 'bi-shield-lock', 'crumbs' => [t('Administration') => null, t('Roles') => null]]);
$byModule = [];
foreach ($perms as $p) $byModule[$p['module']][] = $p;
$isSuper = $role['slug'] === 'super_admin';
$portal = in_array($role['slug'], ['parent', 'student'], true); ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4"><div class="list-group list-group-flush">
            <?php foreach ($roles as $r): ?>
                <a href="<?= e(url('roles', ['role_id' => $r['id']])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $r['id'] == $role['id'] ? 'active' : '' ?>">
                    <span><i class="bi bi-shield<?= $r['slug'] === 'super_admin' ? '-fill-check' : '' ?> me-2"></i><?= e(t($r['name'])) ?></span>
                    <span class="small"><?= (int)$r['users'] ?> <i class="bi bi-person"></i></span></a>
            <?php endforeach; ?>
        </div></div>
        <form method="post" action="<?= e(url('roles/create')) ?>" class="card"><?= csrf_field() ?>
            <div class="card-header"><?= e(t('Create Custom Role')) ?></div>
            <div class="card-body">
                <input name="name" class="form-control mb-2" placeholder="<?= e(t('Role name, e.g. Vice Principal')) ?>" required maxlength="60">
                <input name="description" class="form-control mb-2" placeholder="<?= e(t('Description')) ?>" maxlength="255">
                <select name="copy_from" class="form-select mb-2"><?= options(DB::pairs("SELECT id, name FROM roles WHERE slug NOT IN ('super_admin','parent','student') ORDER BY id"), null, t('Start with no permissions')) ?></select>
                <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> <?= e(t('Create Role')) ?></button>
            </div></form>
    </div>
    <div class="col-lg-8">
        <form method="post" action="<?= e(url('roles/save')) ?>" class="card"><?= csrf_field() ?><input type="hidden" name="role_id" value="<?= (int)$role['id'] ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= e(t($role['name'])) ?> <span class="text-muted small">· <?= e($role['description']) ?></span></span>
                <?php if (!$role['is_system']): ?><button type="button" class="btn btn-sm btn-outline-danger" data-post="<?= e(url('roles/delete', ['id' => $role['id']])) ?>"><i class="bi bi-trash"></i></button><?php endif; ?>
            </div>
            <?php if ($isSuper): ?><div class="card-body"><div class="alert alert-info mb-0"><?= e(t('Super Admin has unrestricted access to every module, user, setting and permission.')) ?></div></div>
            <?php elseif ($portal): ?><div class="card-body"><div class="alert alert-info mb-0"><?= e(t('Parents and students use the separate portal and only see their own records. Staff permissions do not apply.')) ?></div></div>
            <?php else: ?>
            <div class="table-responsive"><table class="table perm-table mb-0">
                <thead><tr><th><?= e(t('Module')) ?></th><th><?= e(t('Permissions')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($byModule as $mod => $list): ?>
                    <tr><td class="fw-semibold text-nowrap"><label class="form-check"><input type="checkbox" class="form-check-input" data-check-all=".pm-<?= e(md5($mod)) ?>"> <?= e(t($mod)) ?></label></td>
                        <td><div class="d-flex flex-wrap gap-3">
                            <?php foreach ($list as $p): ?><label class="form-check"><input type="checkbox" class="form-check-input pm-<?= e(md5($mod)) ?>" name="perms[]" value="<?= (int)$p['id'] ?>" <?= isset($granted[(int)$p['id']]) ? 'checked' : '' ?>> <?= e(t($p['name'])) ?></label><?php endforeach; ?>
                        </div></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <div class="card-body border-top d-flex justify-content-between align-items-center"><span class="small text-muted"><?= e(t('Teachers are additionally restricted to their assigned classes and subjects.')) ?></span><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Permissions')) ?></button></div>
            <?php endif; ?>
        </form>
    </div>
</div>
