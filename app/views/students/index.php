<?php
$actions = '';
if (can('students.import')) {
    $actions .= '<div class="btn-group"><button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> ' . e(t('Export')) . '</button><ul class="dropdown-menu dropdown-menu-end">'
        . '<li><a class="dropdown-item" href="' . e(url_with(['r' => 'students/export', 'format' => 'xls'])) . '"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel</a></li>'
        . '<li><a class="dropdown-item" href="' . e(url_with(['r' => 'students/export', 'format' => 'csv'])) . '"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>'
        . '<li><a class="dropdown-item" target="_blank" href="' . e(url('reports/class_list', ['class_id' => input_int('class_id'), 'section_id' => input_int('section_id')])) . '"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>' . e(t('Print / PDF')) . '</a></li></ul></div>'
        . '<a href="' . e(url('students/import')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-upload"></i> ' . e(t('Import')) . '</a>';
}
if (can('students.create')) {
    $actions .= '<a href="' . e(url('students/create')) . '" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> ' . e(t('New Admission')) . '</a>';
}
partial('page_head', ['title' => t('Students'), 'icon' => 'bi-people', 'actions' => $actions, 'crumbs' => [t('Students') => null]]);
$classId = input_int('class_id');
?>
<div class="card">
    <form class="table-toolbar" method="get">
        <input type="hidden" name="r" value="students">
        <div class="input-group input-group-sm" style="max-width:300px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Name, Reg No, B-Form, father, phone…')) ?>">
        </div>
        <select name="class_id" id="fClass" class="form-select form-select-sm" data-autosubmit><?= options(class_options(), $classId, t('All Classes')) ?></select>
        <select name="section_id" class="form-select form-select-sm" data-sections-for="#fClass" data-autosubmit><?= options($classId ? section_options($classId) : [], input_int('section_id'), t('All Sections')) ?></select>
        <select name="status" class="form-select form-select-sm" data-autosubmit><?= options(['active' => t('Active'), 'withdrawn' => t('Withdrawn'), 'transferred' => t('Transferred'), 'graduated' => t('Graduated'), 'suspended' => t('Suspended'), 'all' => t('All Statuses')], input('status', 'active')) ?></select>
        <select name="gender" class="form-select form-select-sm" data-autosubmit><?= options(['male' => t('Male'), 'female' => t('Female')], input('gender', ''), t('All Genders')) ?></select>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> <?= e(t('Filter')) ?></button>
        <a href="<?= e(url('students')) ?>" class="btn btn-sm btn-link"><?= e(t('Reset')) ?></a>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th></th><th><?= sort_link('reg_no', t('Reg No')) ?></th><th><?= sort_link('name', t('Student')) ?></th><th><?= sort_link('father', t('Father')) ?></th>
                <th><?= sort_link('class', t('Class')) ?></th><th><?= sort_link('roll', t('Roll')) ?></th><th><?= e(t('Contact')) ?></th><th><?= sort_link('admission', t('Admitted')) ?></th><th><?= e(t('Status')) ?></th><th class="text-end"><?= e(t('Actions')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($p['rows'] as $s): ?>
                <tr>
                    <td><?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'])) ?>" class="avatar-sm" alt=""><?php else: ?><span class="avatar-sm avatar-ph"><?= e(mb_substr($s['first_name'], 0, 1)) ?></span><?php endif; ?></td>
                    <td class="fw-semibold text-nowrap"><?= e($s['reg_no']) ?></td>
                    <td><a href="<?= e(url('students/show', ['id' => $s['id']])) ?>" class="fw-semibold text-decoration-none"><?= e(student_name($s)) ?></a><div class="small text-muted"><?= e(t(ucfirst($s['gender']))) ?><?= $s['dob'] ? ' · ' . e(fmt_date($s['dob'])) : '' ?></div></td>
                    <td><?= e($s['father_name']) ?></td>
                    <td class="text-nowrap"><?= e($s['class_name']) ?><?= $s['section_name'] ? ' - ' . e($s['section_name']) : '' ?></td>
                    <td><?= e($s['roll_no']) ?></td>
                    <td class="small"><?= e($s['father_phone'] ?: $s['phone']) ?></td>
                    <td class="small text-nowrap"><?= e(fmt_date($s['admission_date'])) ?></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="table-actions">
                        <a class="btn btn-sm btn-light-soft" href="<?= e(url('students/show', ['id' => $s['id']])) ?>" title="<?= e(t('View')) ?>"><i class="bi bi-eye"></i></a>
                        <?php if (can('students.edit')): ?><a class="btn btn-sm btn-light-soft" href="<?= e(url('students/edit', ['id' => $s['id']])) ?>" title="<?= e(t('Edit')) ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
                        <?php if (can('fees.collect')): ?><a class="btn btn-sm btn-light-soft" href="<?= e(url('fees/collect', ['student_id' => $s['id']])) ?>" title="<?= e(t('Collect Fee')) ?>"><i class="bi bi-cash-coin"></i></a><?php endif; ?>
                        <a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('students/idcard', ['id' => $s['id']])) ?>" title="<?= e(t('ID Card')) ?>"><i class="bi bi-person-vcard"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$p['rows']): ?><tr><td colspan="10"><div class="empty-state"><i class="bi bi-people"></i><?= e(t('No students found.')) ?></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
