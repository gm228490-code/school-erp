<?php /** @var Crud $c @var ?array $row @var ?int $id */
$d = $c->d; ?>
<form method="post" action="<?= e(url('crud/save', ['res' => $c->res])) ?>" enctype="multipart/form-data" data-ajax novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="modal-header">
        <h5 class="modal-title"><i class="bi <?= e($d['icon']) ?> me-2 text-primary"></i><?= e(t($id ? 'Edit' : 'Add')) ?> <?= e(t($d['singular'])) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="row g-3">
            <?php foreach ($d['fields'] as $name => $f): ?>
                <?php if (!empty($f['create_only']) && $id) continue; ?>
                <?= form_field($name, $f, $row[$name] ?? null) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> <?= e(t('Save')) ?></button>
    </div>
</form>
