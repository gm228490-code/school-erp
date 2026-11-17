<form method="post" action="<?= e(url('employees/save', ['type' => $type])) ?>" enctype="multipart/form-data" data-ajax novalidate>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>">
    <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-badge me-2 text-primary"></i><?= e($row ? t('Edit') . ': ' . $row['name'] : ($type === 'teacher' ? t('Add Teacher') : t('Add Staff Member'))) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <?php if ($row): ?><div class="small text-muted mb-2"><?= e(t('Employee ID')) ?>: <b><?= e($row['employee_no']) ?></b></div><?php else: ?><div class="alert alert-light border small py-2"><?= e(t('Employee ID is generated automatically.')) ?></div><?php endif; ?>
        <div class="row g-3">
            <?php foreach ($fields as $name => $f): ?><?= form_field($name, $f, $row[$name] ?? null) ?><?php endforeach; ?>
            <?php if (!$row): ?>
                <div class="col-12"><h6 class="form-section-title"><?= e(t('System Access')) ?></h6></div>
                <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="create_login" value="1" id="cl" <?= $type === 'teacher' ? 'checked' : '' ?>><label class="form-check-label" for="cl"><?= e(t('Create login account')) ?></label></div></div>
                <?php if ($type === 'staff'): ?>
                <div class="col-md-6"><select name="login_role" class="form-select form-select-sm"><?= options(DB::pairs("SELECT slug, name FROM roles WHERE slug NOT IN ('super_admin','parent','student','teacher') ORDER BY id"), 'receptionist') ?></select></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> <?= e(t('Save')) ?></button>
    </div>
</form>
