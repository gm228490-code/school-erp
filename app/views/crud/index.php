<?php
/** @var Crud $c @var array $p */
$d = $c->d;
$res = $c->res;
$actions = '';
if (isset($d['toolbar'])) {
    $actions .= ($d['toolbar'])();
}
$actions .= '<div class="btn-group"><button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> ' . e(t('Export')) . '</button><ul class="dropdown-menu dropdown-menu-end">'
    . '<li><a class="dropdown-item" href="' . e(url_with(['r' => 'crud/export', 'format' => 'xls'])) . '"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel</a></li>'
    . '<li><a class="dropdown-item" href="' . e(url_with(['r' => 'crud/export', 'format' => 'csv'])) . '"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>'
    . '<li><a class="dropdown-item" target="_blank" href="' . e(url_with(['r' => 'crud/print'])) . '"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>' . e(t('Print / PDF')) . '</a></li></ul></div>';
if ($c->allowed('create')) {
    $actions .= '<button class="btn btn-primary btn-sm" data-modal="' . e(url('crud/form', ['res' => $res])) . '" data-size="' . e($d['modal_size']) . '"><i class="bi bi-plus-lg"></i> ' . e(t('Add')) . ' ' . e(t($d['singular'])) . '</button>';
}
partial('page_head', ['title' => t($d['title']), 'icon' => $d['icon'], 'actions' => $actions, 'crumbs' => [t($d['group'] ?? 'Records') => null, t($d['title']) => null]]);
if (!empty($d['intro'])): ?>
    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i><?= e(t($d['intro'])) ?></div>
<?php endif; ?>
<div class="card">
    <form class="table-toolbar" method="get">
        <input type="hidden" name="r" value="crud">
        <input type="hidden" name="res" value="<?= e($res) ?>">
        <?php if ($d['search']): ?>
            <div class="input-group input-group-sm" style="max-width:280px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Search…')) ?>">
            </div>
        <?php endif; ?>
        <?php foreach ($d['filters'] as $key => $f):
            $type = $f['type'] ?? 'select'; ?>
            <?php if ($type === 'select'):
                $opts = is_callable($f['options']) ? $f['options']() : $f['options']; ?>
                <select name="f_<?= e($key) ?>" class="form-select form-select-sm" data-autosubmit>
                    <?= options(array_map('t', array_map('strval', $opts)), $_GET['f_' . $key] ?? '', t($f['label']) . ': ' . t('All')) ?>
                </select>
            <?php else: ?>
                <input type="date" name="f_<?= e($key) ?>" class="form-control form-control-sm" style="max-width:160px" value="<?= e($_GET['f_' . $key] ?? '') ?>" title="<?= e(t($f['label'])) ?>" data-autosubmit>
            <?php endif; ?>
        <?php endforeach; ?>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> <?= e(t('Filter')) ?></button>
        <?php if (!empty($_GET['q']) || array_filter($_GET, fn($v, $k) => str_starts_with((string)$k, 'f_') && $v !== '', ARRAY_FILTER_USE_BOTH)): ?>
            <a href="<?= e(url('crud', ['res' => $res])) ?>" class="btn btn-sm btn-link"><?= e(t('Reset')) ?></a>
        <?php endif; ?>
        <?php if (isset($d['summary'])): ?><div class="ms-auto small fw-semibold"><?= ($d['summary'])() ?></div><?php endif; ?>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th style="width:50px">#</th>
                <?php foreach ($d['columns'] as $k => $col): ?>
                    <th><?= ($col['sort'] ?? true) !== false ? sort_link($k, t($col[0])) : e(t($col[0])) ?></th>
                <?php endforeach; ?>
                <th class="text-end"><?= e(t('Actions')) ?></th>
            </tr></thead>
            <tbody>
            <?php if (!$p['rows']): ?>
                <tr><td colspan="<?= count($d['columns']) + 2 ?>"><div class="empty-state"><i class="bi bi-inbox"></i><?= e(t('No records found.')) ?></div></td></tr>
            <?php endif; ?>
            <?php foreach ($p['rows'] as $i => $row): ?>
                <tr>
                    <td class="text-muted"><?= $p['from'] + $i ?></td>
                    <?php foreach ($d['columns'] as $k => $col): ?>
                        <td><?= $c->cell($k, $row) ?></td>
                    <?php endforeach; ?>
                    <td class="table-actions">
                        <?php if (isset($d['row_actions'])) echo ($d['row_actions'])($row); ?>
                        <?php if ($c->allowed('edit') && (!isset($d['editable']) || ($d['editable'])($row))): ?>
                            <button class="btn btn-sm btn-light-soft" title="<?= e(t('Edit')) ?>" data-modal="<?= e(url('crud/form', ['res' => $res, 'id' => $row['id']])) ?>" data-size="<?= e($d['modal_size']) ?>"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                        <?php if ($c->allowed('delete') && (!isset($d['deletable']) || ($d['deletable'])($row))): ?>
                            <button class="btn btn-sm btn-outline-danger" title="<?= e(t('Delete')) ?>" data-post="<?= e(url('crud/delete', ['res' => $res])) ?>" data-field-id="<?= (int)$row['id'] ?>" data-remove-row data-confirm-text="<?= e(t('Delete this record permanently?')) ?>"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
