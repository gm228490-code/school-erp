<?php
$actions = '<div class="btn-group"><button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> ' . e(t('Export')) . '</button><ul class="dropdown-menu dropdown-menu-end">'
    . '<li><a class="dropdown-item" href="' . e(url('employees/export', ['type' => $type, 'format' => 'xls'])) . '">Excel</a></li><li><a class="dropdown-item" href="' . e(url('employees/export', ['type' => $type, 'format' => 'csv'])) . '">CSV</a></li>'
    . '<li><a class="dropdown-item" target="_blank" href="' . e(url('reports/employees', ['type' => $type])) . '">' . e(t('Print / PDF')) . '</a></li>'
    . '<li><a class="dropdown-item" target="_blank" href="' . e(url('employees/idcard', ['type' => $type, 'all' => 1])) . '">' . e(t('All ID Cards')) . '</a></li></ul></div>';
if (can("$perm.create")) {
    $actions .= '<button class="btn btn-primary btn-sm" data-modal="' . e(url('employees/form', ['type' => $type])) . '" data-size="modal-xl"><i class="bi bi-plus-lg"></i> ' . e($type === 'teacher' ? t('Add Teacher') : t('Add Staff Member')) . '</button>';
}
partial('page_head', ['title' => $title, 'icon' => $type === 'teacher' ? 'bi-person-workspace' : 'bi-person-gear', 'actions' => $actions, 'crumbs' => [t('Human Resource') => null, $title => null]]);
?>
<div class="card">
    <form class="table-toolbar" method="get">
        <input type="hidden" name="r" value="employees"><input type="hidden" name="type" value="<?= e($type) ?>">
        <div class="input-group input-group-sm" style="max-width:280px"><span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Name, ID, CNIC, phone…')) ?>"></div>
        <select name="department_id" class="form-select form-select-sm" data-autosubmit><?= options(DB::pairs('SELECT id,name FROM departments ORDER BY name'), input_int('department_id'), t('All Departments')) ?></select>
        <select name="status" class="form-select form-select-sm" data-autosubmit><?= options(['active' => t('Active'), 'inactive' => t('Inactive'), 'resigned' => t('Resigned'), 'all' => t('All')], input('status', 'active')) ?></select>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> <?= e(t('Filter')) ?></button>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th></th><th><?= sort_link('no', t('ID')) ?></th><th><?= sort_link('name', t('Name')) ?></th><th><?= e(t('Designation')) ?></th><th><?= e(t('Qualification')) ?></th><th><?= e(t('Phone')) ?></th><th><?= sort_link('joining', t('Joined')) ?></th><?php if (can('finance.salary')): ?><th><?= sort_link('salary', t('Salary')) ?></th><?php endif; ?><th><?= e(t('Status')) ?></th><th class="text-end"><?= e(t('Actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($p['rows'] as $r): ?>
                <tr>
                    <td><?php if ($r['photo']): ?><img src="<?= e(upload_url($r['photo'])) ?>" class="avatar-sm" alt=""><?php else: ?><span class="avatar-sm avatar-ph"><?= e(mb_substr($r['name'], 0, 1)) ?></span><?php endif; ?></td>
                    <td class="fw-semibold"><?= e($r['employee_no']) ?></td>
                    <td><a class="fw-semibold text-decoration-none" href="<?= e(url('employees/show', ['type' => $type, 'id' => $r['id']])) ?>"><?= e($r['name']) ?></a><div class="small text-muted"><?= e($r['dept']) ?></div></td>
                    <td><?= e($r['desig']) ?></td><td class="small"><?= e($r['qualification']) ?></td><td class="small"><?= e($r['phone']) ?></td><td class="small"><?= e(fmt_date($r['joining_date'])) ?></td>
                    <?php if (can('finance.salary')): ?><td class="small text-nowrap"><?= e(money($r['basic_salary'] + $r['allowances'])) ?></td><?php endif; ?>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="table-actions">
                        <a class="btn btn-sm btn-light-soft" href="<?= e(url('employees/show', ['type' => $type, 'id' => $r['id']])) ?>"><i class="bi bi-eye"></i></a>
                        <?php if (can("$perm.edit")): ?><button class="btn btn-sm btn-light-soft" data-modal="<?= e(url('employees/form', ['type' => $type, 'id' => $r['id']])) ?>" data-size="modal-xl"><i class="bi bi-pencil"></i></button><?php endif; ?>
                        <a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('employees/idcard', ['type' => $type, 'id' => $r['id']])) ?>"><i class="bi bi-person-vcard"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$p['rows']): ?><tr><td colspan="10"><div class="empty-state"><i class="bi bi-person-workspace"></i><?= e(t('No records found.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
