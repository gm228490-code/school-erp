<?php partial('page_head', ['title' => t('Issue / Return Books'), 'icon' => 'bi-arrow-left-right', 'crumbs' => [t('Library') => null, t('Circulation') => null],
    'actions' => (can('library.issue') ? '<button class="btn btn-primary btn-sm" data-modal="' . e(url('library/issue_form')) . '" data-size="modal-md"><i class="bi bi-box-arrow-right"></i> ' . e(t('Issue Book')) . '</button>' : '')
        . '<a class="btn btn-outline-secondary btn-sm" target="_blank" href="' . e(url('reports/library')) . '"><i class="bi bi-printer"></i> ' . e(t('Library Report')) . '</a>']); ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-card tone-blue"><span class="stat-icon"><i class="bi bi-book"></i></span><div><div class="stat-label"><?= e(t('Total Copies')) ?></div><div class="stat-value"><?= $stats['books'] ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card tone-green"><span class="stat-icon"><i class="bi bi-bookshelf"></i></span><div><div class="stat-label"><?= e(t('Available')) ?></div><div class="stat-value"><?= $stats['available'] ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card tone-violet"><span class="stat-icon"><i class="bi bi-box-arrow-right"></i></span><div><div class="stat-label"><?= e(t('Issued')) ?></div><div class="stat-value"><?= $stats['issued'] ?></div></div></div></div>
    <div class="col-6 col-lg-3"><a href="<?= e(url('library', ['status' => 'overdue'])) ?>" class="card stat-card tone-red"><span class="stat-icon"><i class="bi bi-alarm"></i></span><div><div class="stat-label"><?= e(t('Overdue')) ?></div><div class="stat-value"><?= $stats['overdue'] ?></div></div></a></div>
</div>
<div class="card">
    <form class="table-toolbar" method="get"><input type="hidden" name="r" value="library">
        <input type="search" name="q" class="form-control form-control-sm" style="max-width:260px" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Book, ISBN, member…')) ?>">
        <select name="status" class="form-select form-select-sm" data-autosubmit><?= options(['issued' => t('Issued'), 'overdue' => t('Overdue'), 'returned' => t('Returned'), 'lost' => t('Lost'), 'all' => t('All')], $status) ?></select>
        <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    </form>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th><?= e(t('Book')) ?></th><th><?= e(t('Member')) ?></th><th><?= e(t('Issued')) ?></th><th><?= e(t('Due')) ?></th><th><?= e(t('Returned')) ?></th><th><?= e(t('Fine')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($p['rows'] as $r): $over = $r['status'] === 'issued' && $r['due_date'] < date('Y-m-d'); ?>
            <tr><td><div class="fw-semibold"><?= e($r['title']) ?></div><div class="small text-muted"><?= e($r['isbn']) ?></div></td>
                <td><?= e($r['member']) ?><div class="small text-muted"><?= e(t(ucfirst($r['member_type']))) ?></div></td>
                <td class="small"><?= e(fmt_date($r['issue_date'])) ?></td><td class="small <?= $over ? 'text-danger fw-semibold' : '' ?>"><?= e(fmt_date($r['due_date'])) ?></td><td class="small"><?= e(fmt_date($r['return_date'])) ?></td>
                <td class="small"><?= (float)$r['fine'] ? e(money($r['fine'])) . ($r['fine_paid'] ? ' <i class="bi bi-check-circle text-success"></i>' : '') : '-' ?></td>
                <td><?= $over ? '<span class="badge text-bg-danger badge-soft">' . e(t('Overdue')) . '</span>' : status_badge($r['status']) ?></td>
                <td class="table-actions"><?php if ($r['status'] === 'issued' && can('library.issue')): ?><button class="btn btn-sm btn-success" data-modal="<?= e(url('library/return_form', ['id' => $r['id']])) ?>" data-size="modal-md"><i class="bi bi-box-arrow-in-left"></i> <?= e(t('Return')) ?></button><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$p['rows']): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-book"></i><?= e(t('No records.')) ?></div></td></tr><?php endif; ?>
        </tbody></table></div>
    <?php partial('pagination', ['p' => $p]); ?>
</div>
