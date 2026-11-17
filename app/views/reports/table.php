<?php
$actions = '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . e(url_with(['print' => 1])) . '"><i class="bi bi-printer"></i> ' . e(t('Print / PDF')) . '</a>'
    . '<a class="btn btn-sm btn-outline-success" href="' . e(url_with(['export' => 'xls'])) . '"><i class="bi bi-file-earmark-excel"></i> Excel</a>'
    . '<a class="btn btn-sm btn-outline-secondary" href="' . e(url_with(['export' => 'csv'])) . '"><i class="bi bi-filetype-csv"></i> CSV</a>';
partial('page_head', ['title' => $title, 'icon' => 'bi-file-earmark-bar-graph', 'actions' => $actions, 'crumbs' => [t('Reports') => url('reports'), $title => null]]);
?>
<?php if ($filters): ?>
<div class="card mb-3"><div class="card-body">
    <form method="get" class="row g-2 align-items-end"><input type="hidden" name="r" value="<?= e($_GET['r']) ?>">
        <?php if (isset($filters['from'])): ?>
            <div class="col-md-3"><label class="form-label"><?= e(t('From')) ?></label><input type="date" name="from" class="form-control" value="<?= e($filters['from']) ?>"></div>
            <div class="col-md-3"><label class="form-label"><?= e(t('To')) ?></label><input type="date" name="to" class="form-control" value="<?= e($filters['to']) ?>"></div>
        <?php endif; ?>
        <?php if (isset($filters['month'])): ?><div class="col-md-3"><label class="form-label"><?= e(t('Month')) ?></label><input type="month" name="month" class="form-control" value="<?= e($filters['month']) ?>"></div><?php endif; ?>
        <?php if (isset($filters['class_id'])): ?><div class="col-md-3"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="repClass" class="form-select"><?= options(class_options(), input_int('class_id'), t('All Classes')) ?></select></div><?php endif; ?>
        <?php if (isset($filters['section_id'])): ?><div class="col-md-2"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#repClass"><?= options(input_int('class_id') ? section_options(input_int('class_id')) : [], input_int('section_id'), t('All')) ?></select></div><?php endif; ?>
        <?php if (isset($filters['group'])): ?><div class="col-md-2"><label class="form-label"><?= e(t('Group by')) ?></label><select name="group" class="form-select"><?= options(['day' => t('Day'), 'month' => t('Month')], input('group', 'day')) ?></select></div><?php endif; ?>
        <?php if (isset($filters['overdue'])): ?><div class="col-md-2"><label class="form-check mt-4"><input type="checkbox" class="form-check-input" name="overdue" value="1" <?= input('overdue') ? 'checked' : '' ?>> <?= e(t('Overdue only')) ?></label></div><?php endif; ?>
        <?php if (isset($filters['type'])): ?><div class="col-md-3"><label class="form-label"><?= e(t('Type')) ?></label><select name="type" class="form-select"><?= options(['teacher' => t('Teachers'), 'staff' => t('Staff')], $filters['type']) ?></select></div><?php endif; ?>
        <?php if (isset($filters['mode'])): ?><div class="col-md-3"><label class="form-label"><?= e(t('Show')) ?></label><select name="mode" class="form-select"><?= options(['issued' => t('Books issued'), 'books' => t('Stock')], input('mode', 'issued')) ?></select></div><?php endif; ?>
        <?php if (isset($filters['route_id'])): ?><div class="col-md-3"><label class="form-label"><?= e(t('Route')) ?></label><select name="route_id" class="form-select"><?= options(DB::pairs('SELECT id,name FROM transport_routes ORDER BY name'), input_int('route_id'), t('All Routes')) ?></select></div><?php endif; ?>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> <?= e(t('Show')) ?></button></div>
    </form>
</div></div>
<?php endif; ?>
<div class="card">
    <?php if ($subtitle): ?><div class="card-header small"><?= e($subtitle) ?></div><?php endif; ?>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>#</th><?php foreach ($headers as $h): ?><th><?= e(t($h)) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?><tr><td class="text-muted"><?= $i + 1 ?></td><?php foreach ($r as $c): ?><td><?= e($c) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="<?= count($headers) + 1 ?>"><div class="empty-state"><i class="bi bi-inbox"></i><?= e(t('No records found.')) ?></div></td></tr><?php endif; ?>
        </tbody>
        <?php if ($footer && $rows): ?><tfoot><tr class="fw-bold"><td></td><?php foreach ($footer as $c): ?><td><?= e($c) ?></td><?php endforeach; ?></tr></tfoot><?php endif; ?>
    </table></div>
</div>
