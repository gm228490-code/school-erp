<?php partial('page_head', ['title' => t('Backup & Restore'), 'icon' => 'bi-database-down', 'crumbs' => [t('Administration') => null, t('Backup') => null],
    'actions' => '<form method="post" action="' . e(url('system/create')) . '">' . csrf_field() . '<button class="btn btn-primary btn-sm"><i class="bi bi-database-add"></i> ' . e(t('Create Backup Now')) . '</button></form>']); ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4"><div class="card-header"><?= e(t('Saved Backups')) ?> <span class="small text-muted">(storage/backups)</span></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th><?= e(t('File')) ?></th><th><?= e(t('Created')) ?></th><th><?= e(t('Size')) ?></th><th></th></tr></thead><tbody>
                <?php foreach ($files as $f): ?>
                    <tr><td class="small"><?= e($f['name']) ?></td><td class="small"><?= e(date('d-M-Y h:i A', $f['time'])) ?></td><td class="small"><?= number_format($f['size'] / 1024, 1) ?> KB</td>
                        <td class="table-actions"><a class="btn btn-sm btn-light-soft" href="<?= e(url('system/download', ['file' => $f['name']])) ?>"><i class="bi bi-download"></i></a>
                            <button class="btn btn-sm btn-outline-danger" data-post="<?= e(url('system/delete')) ?>" data-field-file="<?= e($f['name']) ?>" data-remove-row><i class="bi bi-trash"></i></button></td></tr>
                <?php endforeach; ?>
                <?php if (!$files): ?><tr><td colspan="4" class="text-muted p-3"><?= e(t('No backups yet.')) ?></td></tr><?php endif; ?>
                </tbody></table></div></div>
        <div class="card"><div class="card-header"><?= e(t('Database Tables')) ?></div><div class="card-body small" style="columns:3">
            <?php foreach ($stats as $s): ?><div><?= e($s['t']) ?> <span class="text-muted">(<?= (int)$s['r'] ?>)</span></div><?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-danger">
            <div class="card-header text-danger"><i class="bi bi-exclamation-triangle"></i> <?= e(t('Restore Database')) ?></div>
            <div class="card-body">
                <?php if (!Auth::isSuper()): ?><div class="alert alert-warning small mb-0"><?= e(t('Only the Super Admin can restore the database.')) ?></div><?php else: ?>
                <p class="small text-muted"><?= e(t('Restoring replaces ALL current data with the backup. A safety backup of the current data is created automatically before restoring.')) ?></p>
                <form method="post" action="<?= e(url('system/restore')) ?>" enctype="multipart/form-data" data-confirm="<?= e(t('This will overwrite all data. Continue?')) ?>"><?= csrf_field() ?>
                    <label class="form-label"><?= e(t('Choose a saved backup')) ?></label>
                    <select name="file" class="form-select mb-2"><?= options(array_combine(array_column($files, 'name'), array_column($files, 'name')) ?: [], null, t('-- or upload a file below --')) ?></select>
                    <input type="file" name="sql" class="form-control mb-3" accept=".sql">
                    <label class="form-label"><?= e(t('Type RESTORE to confirm')) ?></label>
                    <input name="confirm_text" class="form-control mb-3" autocomplete="off" required>
                    <button class="btn btn-danger w-100"><i class="bi bi-arrow-counterclockwise"></i> <?= e(t('Restore')) ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="alert alert-light border small mt-3"><i class="bi bi-info-circle"></i> <?= e(t('Uploaded files (photos, documents) are stored in the uploads/ folder. Include that folder in your server or hosting file backups.')) ?></div>
    </div>
</div>
