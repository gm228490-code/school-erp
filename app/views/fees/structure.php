<?php partial('page_head', ['title' => t('Class-wise Fee Structure'), 'icon' => 'bi-table', 'crumbs' => [t('Fees') => null, t('Fee Structure') => null],
    'actions' => '<span class="badge text-bg-primary badge-soft fs-6">' . e(t('Session')) . ': ' . e(current_session()['name'] ?? '') . '</span>']); ?>
<div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i><?= e(t('Enter the amount of each fee category per class for this session. Leave blank or 0 when not applicable. Transport fee comes from each route; individual rates can be set in Student Special Fees.')) ?></div>
<?php if (!$classes): ?><div class="alert alert-warning"><?= e(t('Create classes first (Academics → Classes).')) ?></div><?php else: ?>
<form method="post" action="<?= e(url('fees/save_structure')) ?>" class="card">
    <?= csrf_field() ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead><tr><th><?= e(t('Class')) ?></th><?php foreach ($cats as $c): ?><th class="text-center"><?= e($c['name']) ?><div class="fw-normal text-muted" style="text-transform:none"><?= e(t(ucwords(str_replace('_', ' ', $c['frequency'])))) ?></div></th><?php endforeach; ?><th><?= e(t('Monthly Total')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($classes as $cl): $mt = 0; ?>
                <tr><th class="text-nowrap"><?= e($cl['name']) ?></th>
                    <?php foreach ($cats as $c): $v = $amounts[$cl['id']][$c['id']] ?? ''; if ($c['frequency'] === 'monthly') $mt += (float)$v; ?>
                        <td><input type="number" step="1" min="0" class="form-control form-control-sm text-end" style="min-width:95px" name="fee[<?= (int)$cl['id'] ?>][<?= (int)$c['id'] ?>]" value="<?= $v !== '' ? e((float)$v) : '' ?>"></td>
                    <?php endforeach; ?>
                    <td class="fw-semibold text-nowrap"><?= e(money($mt)) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body border-top text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Fee Structure')) ?></button></div>
</form>
<?php endif; ?>
