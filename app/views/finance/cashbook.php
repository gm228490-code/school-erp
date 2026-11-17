<?php partial('page_head', ['title' => t('Daily Cash Book'), 'icon' => 'bi-journal-bookmark', 'crumbs' => [t('Accounts') => null, t('Cash Book') => null],
    'actions' => '<a target="_blank" class="btn btn-sm btn-outline-secondary" href="' . e(url_with(['print' => 1])) . '"><i class="bi bi-printer"></i> ' . e(t('Print / PDF')) . '</a>']); ?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="d-flex gap-2 align-items-end flex-wrap"><input type="hidden" name="r" value="finance/cashbook">
        <div><label class="form-label"><?= e(t('Date')) ?></label><input type="date" name="date" value="<?= e($date) ?>" class="form-control" data-autosubmit></div>
        <a class="btn btn-light-soft" href="<?= e(url('finance/cashbook', ['date' => date('Y-m-d', strtotime($date . ' -1 day'))])) ?>"><i class="bi bi-chevron-left"></i></a>
        <a class="btn btn-light-soft" href="<?= e(url('finance/cashbook', ['date' => date('Y-m-d', strtotime($date . ' +1 day'))])) ?>"><i class="bi bi-chevron-right"></i></a>
    </form>
</div></div>
<div class="card"><div class="table-responsive"><?php include __DIR__ . '/_cashbook_table.php'; ?></div></div>
