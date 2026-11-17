<?php partial('page_head', ['title' => t('Finance Overview'), 'icon' => 'bi-graph-up-arrow', 'crumbs' => [t('Accounts') => null, t('Overview') => null],
    'actions' => '<a target="_blank" class="btn btn-sm btn-outline-secondary" href="' . e(url_with(['print' => 1])) . '"><i class="bi bi-printer"></i> ' . e(t('Income & Expense Statement')) . '</a>']); ?>
<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2 align-items-end"><input type="hidden" name="r" value="finance">
        <div class="col-md-3"><label class="form-label"><?= e(t('From')) ?></label><input type="date" name="from" value="<?= e($from) ?>" class="form-control"></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('To')) ?></label><input type="date" name="to" value="<?= e($to) ?>" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><?= e(t('Apply')) ?></button></div>
        <div class="col-md-4 d-flex gap-1 flex-wrap">
            <a class="btn btn-sm btn-light-soft" href="<?= e(url('finance', ['from' => date('Y-m-d'), 'to' => date('Y-m-d')])) ?>"><?= e(t('Today')) ?></a>
            <a class="btn btn-sm btn-light-soft" href="<?= e(url('finance', ['from' => date('Y-m-01'), 'to' => date('Y-m-t')])) ?>"><?= e(t('This month')) ?></a>
            <a class="btn btn-sm btn-light-soft" href="<?= e(url('finance', ['from' => current_session()['start_date'] ?? date('Y-01-01'), 'to' => current_session()['end_date'] ?? date('Y-12-31')])) ?>"><?= e(t('This session')) ?></a>
        </div>
    </form>
</div></div>
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card stat-card tone-blue"><span class="stat-icon"><i class="bi bi-cash-coin"></i></span><div><div class="stat-label"><?= e(t('Fee Collection')) ?></div><div class="stat-value"><?= e(money($tot['fees'])) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card stat-card tone-cyan"><span class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></span><div><div class="stat-label"><?= e(t('Other Income')) ?></div><div class="stat-value"><?= e(money($tot['other'])) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card stat-card tone-red"><span class="stat-icon"><i class="bi bi-box-arrow-up"></i></span><div><div class="stat-label"><?= e(t('Expenses + Salaries')) ?></div><div class="stat-value"><?= e(money($tot['outflow'])) ?></div><div class="stat-sub"><?= e(t('Salaries')) ?>: <?= e(money($tot['salaries'])) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card stat-card tone-<?= $tot['net'] >= 0 ? 'green' : 'amber' ?>"><span class="stat-icon"><i class="bi bi-piggy-bank"></i></span><div><div class="stat-label"><?= e(t('Net Balance')) ?></div><div class="stat-value"><?= e(money($tot['net'])) ?></div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-xl-8"><div class="card h-100"><div class="card-header"><?= e(t('Monthly Income vs Expenses')) ?></div><div class="card-body"><div class="chart-box"><canvas id="finMonthly" role="img" aria-label="<?= e(t('Monthly Income vs Expenses')) ?>"></canvas></div></div></div></div>
    <div class="col-xl-4"><div class="card h-100"><div class="card-header"><?= e(t('Breakdown')) ?></div><div class="card-body small">
        <h6 class="fw-semibold"><?= e(t('Income')) ?></h6>
        <div class="d-flex justify-content-between border-bottom py-1"><span><?= e(t('Fee Collection')) ?></span><b><?= e(money($tot['fees'])) ?></b></div>
        <?php foreach ($incCats as $c): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?= e($c['name']) ?></span><span><?= e(money($c['total'])) ?></span></div><?php endforeach; ?>
        <h6 class="fw-semibold mt-3"><?= e(t('Expenses')) ?></h6>
        <div class="d-flex justify-content-between border-bottom py-1"><span><?= e(t('Salaries')) ?></span><b><?= e(money($tot['salaries'])) ?></b></div>
        <?php foreach ($expCats as $c): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?= e($c['name']) ?></span><span><?= e(money($c['total'])) ?></span></div><?php endforeach; ?>
    </div></div></div>
    <div class="col-12"><div class="card"><div class="card-header"><?= e(t('Monthly Statement')) ?></div><div class="table-responsive">
        <table class="table table-sm table-hover mb-0"><thead><tr><th><?= e(t('Month')) ?></th><th class="text-end"><?= e(t('Fees')) ?></th><th class="text-end"><?= e(t('Other Income')) ?></th><th class="text-end"><?= e(t('Expenses')) ?></th><th class="text-end"><?= e(t('Salaries')) ?></th><th class="text-end"><?= e(t('Net')) ?></th></tr></thead><tbody>
        <?php foreach (array_reverse($months) as $m): ?><tr><td><?= e($m['label']) ?></td><td class="text-end"><?= e(money($m['fees'])) ?></td><td class="text-end"><?= e(money($m['other'])) ?></td><td class="text-end"><?= e(money($m['expenses'])) ?></td><td class="text-end"><?= e(money($m['salaries'])) ?></td><td class="text-end fw-semibold <?= $m['net'] < 0 ? 'text-danger' : 'text-success' ?>"><?= e(money($m['net'])) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div></div>
</div>
<?php ob_start(); ?>
<script>
(function () {
  const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  const C1 = dark ? '#4f86f0' : '#2563eb', C2 = dark ? '#d9622b' : '#e8590c', surface = dark ? '#131c2f' : '#ffffff';
  const money = v => <?= json_encode(setting('currency_symbol', 'Rs.')) ?> + ' ' + Number(v).toLocaleString();
  new Chart(document.getElementById('finMonthly'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($months, 'label')) ?>, datasets: [
      { label: <?= json_encode(t('Income')) ?>, data: <?= json_encode(array_column($months, 'income')) ?>, backgroundColor: C1, borderRadius: 4, borderSkipped: 'bottom', borderColor: surface, borderWidth: { left: 1, right: 1 }, maxBarThickness: 22 },
      { label: <?= json_encode(t('Expenses')) ?>, data: <?= json_encode(array_column($months, 'outflow')) ?>, backgroundColor: C2, borderRadius: 4, borderSkipped: 'bottom', borderColor: surface, borderWidth: { left: 1, right: 1 }, maxBarThickness: 22 }
    ]},
    options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + money(c.raw) } } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString() } } } }
  });
})();
</script>
<?php $scripts = ob_get_clean(); ?>
