<?php
$canEdit = can('timetable.manage');
$actions = '<a href="' . e(url('timetable/teacher')) . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-workspace"></i> ' . e(t('Teacher Timetable')) . '</a>';
if ($section) {
    $actions .= '<a target="_blank" href="' . e(url('timetable', ['section_id' => $sectionId, 'print' => 1])) . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> ' . e(t('Print')) . '</a>';
}
partial('page_head', ['title' => t('Class Timetable'), 'icon' => 'bi-calendar3-week', 'actions' => $actions, 'crumbs' => [t('Academics') => null, t('Timetable') => null]]);
$subjects = $section ? subject_options((int)$section['class_id']) : [];
$teachers = teacher_options();
?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="timetable">
        <div class="col-md-6"><label class="form-label"><?= e(t('Class & Section')) ?></label><select name="section_id" class="form-select" data-autosubmit><?= options(section_options(), $sectionId, t('-- Select --')) ?></select></div>
    </form>
    <?php if (!$periods): ?><div class="alert alert-warning mt-3 mb-0"><?= e(t('Define periods first.')) ?> <a href="<?= e(url('crud', ['res' => 'periods'])) ?>"><?= e(t('Periods')) ?></a></div><?php endif; ?>
</div></div>
<?php if ($section && $periods): ?>
<form method="post" action="<?= e(url('timetable/save')) ?>" class="card">
    <?= csrf_field() ?><input type="hidden" name="section_id" value="<?= (int)$sectionId ?>">
    <div class="card-header"><?= e($section['class_name'] . ' - ' . $section['name']) ?> · <?= e(current_session()['name'] ?? '') ?></div>
    <div class="table-responsive">
        <table class="table table-bordered tt-table mb-0">
            <thead><tr><th><?= e(t('Day')) ?></th><?php foreach ($periods as $p): ?><th><?= e($p['name']) ?><div class="fw-normal small"><?= e(fmt_time($p['start_time'])) ?> – <?= e(fmt_time($p['end_time'])) ?></div></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($days as $d => $dn): ?>
                <tr><th><?= e(t($dn)) ?></th>
                <?php foreach ($periods as $p): $c = $grid[$d][$p['id']] ?? null; ?>
                    <?php if ($p['is_break']): ?><td class="tt-break"><?= e($p['name']) ?></td>
                    <?php elseif ($canEdit): ?>
                        <td><select name="slot[<?= $d ?>][<?= (int)$p['id'] ?>][subject]" class="form-select form-select-sm mb-1"><?= options($subjects, $c['subject_id'] ?? null, '—') ?></select>
                            <select name="slot[<?= $d ?>][<?= (int)$p['id'] ?>][teacher]" class="form-select form-select-sm mb-1"><?= options($teachers, $c['teacher_id'] ?? null, '—') ?></select>
                            <input name="slot[<?= $d ?>][<?= (int)$p['id'] ?>][room]" class="form-control form-control-sm" placeholder="<?= e(t('Room')) ?>" value="<?= e($c['room'] ?? '') ?>"></td>
                    <?php else: ?>
                        <td><?php if ($c): ?><div class="tt-cell"><div class="fw-semibold"><?= e($c['subject']) ?></div><div class="small text-muted"><?= e($c['teacher']) ?></div></div><?php endif; ?></td>
                    <?php endif; ?>
                <?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($canEdit): ?><div class="card-body border-top d-flex justify-content-between"><span class="small text-muted"><?= e(t('Teacher clashes (same teacher in two classes at the same time) are detected and skipped.')) ?></span><button class="btn btn-primary"><i class="bi bi-save"></i> <?= e(t('Save Timetable')) ?></button></div><?php endif; ?>
</form>
<?php endif; ?>
