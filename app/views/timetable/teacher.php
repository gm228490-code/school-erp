<?php partial('page_head', ['title' => t('Teacher Timetable'), 'icon' => 'bi-person-workspace', 'crumbs' => [t('Timetable') => url('timetable'), t('Teacher') => null],
    'actions' => $teacher ? '<a target="_blank" href="' . e(url('timetable/teacher', ['teacher_id' => $teacherId, 'print' => 1])) . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> ' . e(t('Print')) . '</a>' : '']); ?>
<?php if (!Auth::scoped()): ?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2"><input type="hidden" name="r" value="timetable/teacher">
        <div class="col-md-6"><select name="teacher_id" class="form-select" data-autosubmit><?= options(teacher_options(), $teacherId, t('-- Select teacher --')) ?></select></div></form>
</div></div>
<?php endif; ?>
<?php if ($teacher): $count = array_sum(array_map('count', $grid)); ?>
<div class="card">
    <div class="card-header"><?= e($teacher['name']) ?> · <?= $count ?> <?= e(t('periods / week')) ?></div>
    <div class="table-responsive"><?php include __DIR__ . '/_grid.php'; ?></div>
</div>
<?php endif; ?>
