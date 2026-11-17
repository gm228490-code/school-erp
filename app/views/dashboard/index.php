<?php
/** @var array $d */
$u = Auth::user();
$hour = (int)date('G');
$greet = $hour < 12 ? t('Good morning') : ($hour < 17 ? t('Good afternoon') : t('Good evening'));
$card = function (string $label, string $value, string $icon, string $tone, string $sub = '', string $href = '') {
    $tag = $href ? 'a href="' . e($href) . '"' : 'div';
    $end = $href ? 'a' : 'div';
    return '<div class="col-6 col-md-4 col-xl-3"><' . $tag . ' class="card stat-card tone-' . $tone . '"><span class="stat-icon"><i class="bi ' . $icon . '"></i></span><div class="min-w-0"><div class="stat-label">' . e($label) . '</div><div class="stat-value">' . e($value) . '</div>' . ($sub ? '<div class="stat-sub">' . $sub . '</div>' : '') . '</div></' . $end . '></div>';
};
?>
<div class="page-head">
    <div>
        <h1><span class="ph-icon"><i class="bi bi-grid-1x2"></i></span><?= e($greet) ?>, <?= e(explode(' ', $u['name'])[0]) ?></h1>
        <div class="text-muted small"><?= e(date('l, d F Y')) ?> &middot; <?= e(t('Session')) ?> <?= e(current_session()['name'] ?? '-') ?></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (can('students.create')): ?><a href="<?= e(url('students/create')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> <?= e(t('New Admission')) ?></a><?php endif; ?>
        <?php if (can('fees.collect')): ?><a href="<?= e(url('fees/collect')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-cash-coin"></i> <?= e(t('Collect Fee')) ?></a><?php endif; ?>
        <?php if (can('attendance.mark')): ?><a href="<?= e(url('attendance/students')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-square"></i> <?= e(t('Mark Attendance')) ?></a><?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <?= $card(t('Total Students'), number_format($d['students']), 'bi-people-fill', 'blue', e(t('Boys')) . ' ' . $d['boys'] . ' · ' . e(t('Girls')) . ' ' . $d['girls'], can('students.view') ? url('students') : '') ?>
    <?= $card(t('Total Teachers'), number_format($d['teachers']), 'bi-person-workspace', 'violet', '', can('teachers.view') ? url('employees', ['type' => 'teacher']) : '') ?>
    <?= $card(t('Total Staff'), number_format($d['staff']), 'bi-person-gear', 'slate', '', can('staff.view') ? url('employees', ['type' => 'staff']) : '') ?>
    <?= $card(t('Classes / Sections'), $d['classes'] . ' / ' . $d['sections'], 'bi-building', 'cyan', e(t('Subjects')) . ': ' . $d['subjects'], can('academics.view') ? url('crud', ['res' => 'classes']) : '') ?>
    <?= $card(t("Today's Attendance"), $d['att_marked'] ? $d['att_pct'] . '%' : t('Not marked'), 'bi-calendar-check', 'green', e(t('Marked')) . ': ' . $d['att_marked'] . ' / ' . $d['students'], can('attendance.view') ? url('attendance/report') : '') ?>
    <?= $card(t('Present Students'), number_format($d['att_present']), 'bi-person-check-fill', 'green', e(t('On leave')) . ': ' . $d['att_leave']) ?>
    <?= $card(t('Absent Students'), number_format($d['att_absent']), 'bi-person-x-fill', 'red', e(t('Staff present')) . ': ' . $d['emp_present'] . ' / ' . ($d['teachers'] + $d['staff'])) ?>
    <?php if (isset($d['fee_today'])): ?>
        <?= $card(t("Today's Fee Collection"), money($d['fee_today']), 'bi-cash-coin', 'blue', $d['fee_today_count'] . ' ' . e(t('receipts')), url('fees/payments', ['from' => date('Y-m-d'), 'to' => date('Y-m-d')])) ?>
        <?= $card(t('Monthly Fee Collection'), money($d['fee_month']), 'bi-calendar2-month', 'cyan', e(date('F Y'))) ?>
        <?= $card(t('Pending Fees'), money($d['fee_pending']), 'bi-hourglass-split', 'amber', $d['fee_defaulters'] . ' ' . e(t('overdue students')), url('reports/outstanding')) ?>
    <?php endif; ?>
    <?php if (isset($d['exp_month'])): ?>
        <?= $card(t('Expenses (this month)'), money($d['exp_month']), 'bi-box-arrow-up', 'red', e(t('incl. salaries')), url('finance')) ?>
    <?php endif; ?>
    <?php if ($d['pending_leaves'] || $d['overdue_books']): ?>
        <?= $card(t('Pending Actions'), (string)($d['pending_leaves'] + $d['overdue_books']), 'bi-exclamation-diamond', 'amber', e(t('Leaves')) . ': ' . $d['pending_leaves'] . ' · ' . e(t('Overdue books')) . ': ' . $d['overdue_books']) ?>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <?php if (isset($d['chart_fin'])): ?>
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><?= e(t('Monthly Income and Expenses')) ?></span><span class="small text-muted"><?= e(t('Last 12 months')) ?></span></div>
            <div class="card-body"><div class="chart-box"><canvas id="finChart" role="img" aria-label="<?= e(t('Monthly Income and Expenses')) ?>"></canvas></div></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="<?= isset($d['chart_fin']) ? 'col-xl-4' : 'col-xl-6' ?>">
        <div class="card h-100">
            <div class="card-header"><?= e(t('Students by Class')) ?></div>
            <div class="card-body"><div class="chart-box"><canvas id="classChart" role="img" aria-label="<?= e(t('Students by Class')) ?>"></canvas></div></div>
        </div>
    </div>
    <div class="<?= isset($d['chart_fin']) ? 'col-xl-12' : 'col-xl-6' ?>">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><?= e(t('Attendance Statistics')) ?></span><span class="small text-muted"><?= e(t('Recent school days')) ?></span></div>
            <div class="card-body">
                <?php if ($d['chart_att']['labels']): ?><div class="chart-box" style="height:240px"><canvas id="attChart" role="img" aria-label="<?= e(t('Attendance Statistics')) ?>"></canvas></div>
                <?php else: ?><div class="empty-state"><i class="bi bi-calendar-x"></i><?= e(t('No attendance recorded yet.')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if ($d['my_schedule']): ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100"><div class="card-header"><i class="bi bi-calendar3-week me-1"></i><?= e(t("My Classes Today")) ?></div>
            <div class="card-body list-feed">
                <?php foreach ($d['my_schedule'] as $s): ?>
                    <div class="feed-item"><span class="feed-icon"><i class="bi bi-clock"></i></span><div><div class="fw-semibold"><?= e($s['subject'] ?? '-') ?> · <?= e($s['class_name'] . ' ' . $s['section_name']) ?></div><div class="small text-muted"><?= e($s['period']) ?> · <?= e(fmt_time($s['start_time'])) ?> – <?= e(fmt_time($s['end_time'])) ?> <?= $s['room'] ? '· ' . e($s['room']) : '' ?></div></div></div>
                <?php endforeach; ?>
            </div></div>
    </div>
    <?php endif; ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-person-plus me-1"></i><?= e(t('Recent Admissions')) ?></span><?php if (can('students.view')): ?><a href="<?= e(url('students')) ?>" class="small"><?= e(t('View all')) ?></a><?php endif; ?></div>
            <div class="card-body list-feed">
                <?php foreach ($d['recent_admissions'] as $s): ?>
                    <a class="feed-item text-decoration-none text-reset" href="<?= e(url('students/show', ['id' => $s['id']])) ?>">
                        <?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'])) ?>" class="avatar-sm" alt=""><?php else: ?><span class="avatar-sm avatar-ph"><?= e(mb_substr($s['first_name'], 0, 1)) ?></span><?php endif; ?>
                        <div class="min-w-0"><div class="fw-semibold text-truncate"><?= e(student_name($s)) ?></div><div class="small text-muted"><?= e($s['reg_no']) ?> · <?= e($s['class_name'] . ($s['section_name'] ? ' - ' . $s['section_name'] : '')) ?> · <?= e(fmt_date($s['admission_date'])) ?></div></div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$d['recent_admissions']): ?><div class="empty-state py-3"><?= e(t('No admissions yet.')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pencil-square me-1"></i><?= e(t('Upcoming Exams')) ?></div>
            <div class="card-body list-feed">
                <?php foreach ($d['upcoming_exams'] as $x): ?>
                    <div class="feed-item"><span class="feed-icon"><i class="bi bi-journal-check"></i></span><div><div class="fw-semibold"><?= e($x['name']) ?></div>
                        <div class="small text-muted"><?= e(t(ucwords(str_replace('_', ' ', $x['exam_type'])))) ?> · <?= e(fmt_date($x['start_date'])) ?><?= $x['end_date'] ? ' – ' . e(fmt_date($x['end_date'])) : '' ?><?= $x['next_paper'] ? ' · ' . e(t('Next paper')) . ': ' . e(fmt_date($x['next_paper'])) : '' ?></div></div></div>
                <?php endforeach; ?>
                <?php if (!$d['upcoming_exams']): ?><div class="empty-state py-3"><?= e(t('No upcoming exams.')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-megaphone me-1"></i><?= e(t('Latest Announcements')) ?></span><?php if (can('notices.view')): ?><a href="<?= e(url('crud', ['res' => 'notices'])) ?>" class="small"><?= e(t('View all')) ?></a><?php endif; ?></div>
            <div class="card-body list-feed">
                <?php foreach ($d['notices'] as $n): ?>
                    <div class="feed-item"><span class="feed-icon"><i class="bi <?= ['exam' => 'bi-trophy', 'holiday' => 'bi-sun', 'fee' => 'bi-cash', 'event' => 'bi-calendar-event'][$n['notice_type']] ?? 'bi-megaphone' ?>"></i></span>
                        <div class="min-w-0"><div class="fw-semibold"><?= e($n['title']) ?></div><div class="small text-muted text-truncate"><?= e(mb_strimwidth($n['body'], 0, 90, '…')) ?></div><div class="small text-muted"><?= e(fmt_date($n['publish_date'])) ?></div></div></div>
                <?php endforeach; ?>
                <?php if (!$d['notices']): ?><div class="empty-state py-3"><?= e(t('No announcements.')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($d['recent_payments']): ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-receipt me-1"></i><?= e(t('Latest Fee Receipts')) ?></span><a href="<?= e(url('fees/payments')) ?>" class="small"><?= e(t('View all')) ?></a></div>
            <div class="card-body list-feed">
                <?php foreach ($d['recent_payments'] as $p): ?>
                    <a class="feed-item text-decoration-none text-reset" href="<?= e(url('fees/receipt', ['id' => $p['id']])) ?>" target="_blank"><span class="feed-icon"><i class="bi bi-cash"></i></span>
                        <div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate"><?= e(student_name($p)) ?></div><div class="small text-muted"><?= e($p['receipt_no']) ?> · <?= e(fmt_date($p['paid_on'])) ?></div></div><div class="fw-semibold text-nowrap"><?= e(money($p['amount'])) ?></div></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-bell me-1"></i><?= e(t('Latest Notifications')) ?></span><a href="<?= e(url('notifications')) ?>" class="small"><?= e(t('View all')) ?></a></div>
            <div class="card-body list-feed">
                <?php foreach ($d['notifications'] as $n): ?>
                    <a class="feed-item text-decoration-none text-reset" href="<?= e(url('notifications/open', ['id' => $n['id']])) ?>"><span class="feed-icon"><i class="bi bi-bell"></i></span><div class="min-w-0"><div class="fw-semibold <?= $n['is_read'] ? '' : 'text-primary' ?>"><?= e($n['title']) ?></div><div class="small text-muted text-truncate"><?= e($n['message']) ?></div></div></a>
                <?php endforeach; ?>
                <?php if (!$d['notifications']): ?><div class="empty-state py-3"><?= e(t('No notifications yet.')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
  const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  // Validated categorical pair (CVD-safe) per theme
  const C1 = dark ? '#4f86f0' : '#2563eb', C2 = dark ? '#d9622b' : '#e8590c';
  const surface = dark ? '#131c2f' : '#ffffff';
  const money = v => <?= json_encode(setting('currency_symbol', 'Rs.')) ?> + ' ' + Number(v).toLocaleString();
  const tip = { mode: 'index', intersect: false };
  <?php if (isset($d['chart_fin'])): ?>
  new Chart(document.getElementById('finChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($d['chart_fin']['labels']) ?>, datasets: [
      { label: <?= json_encode(t('Income')) ?>, data: <?= json_encode($d['chart_fin']['income']) ?>, backgroundColor: C1, borderRadius: 4, borderSkipped: 'bottom', borderColor: surface, borderWidth: { left: 1, right: 1 }, maxBarThickness: 22 },
      { label: <?= json_encode(t('Expenses')) ?>, data: <?= json_encode($d['chart_fin']['expense']) ?>, backgroundColor: C2, borderRadius: 4, borderSkipped: 'bottom', borderColor: surface, borderWidth: { left: 1, right: 1 }, maxBarThickness: 22 }
    ]},
    options: { maintainAspectRatio: false, interaction: tip, plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + money(c.raw) } }, legend: { position: 'top', align: 'end' } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString() } } } }
  });
  <?php endif; ?>
  new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_keys($d['chart_class'])) ?>, datasets: [{ label: <?= json_encode(t('Students')) ?>, data: <?= json_encode(array_map('intval', array_values($d['chart_class']))) ?>, backgroundColor: C1, borderRadius: 4, maxBarThickness: 18 }] },
    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
  });
  <?php if ($d['chart_att']['labels']): ?>
  new Chart(document.getElementById('attChart'), {
    type: 'line',
    data: { labels: <?= json_encode($d['chart_att']['labels']) ?>, datasets: [
      { label: <?= json_encode(t('Present')) ?>, data: <?= json_encode($d['chart_att']['present']) ?>, borderColor: C1, backgroundColor: C1, borderWidth: 2, pointRadius: 4, pointBorderColor: surface, pointBorderWidth: 2, tension: .3 },
      { label: <?= json_encode(t('Absent')) ?>, data: <?= json_encode($d['chart_att']['absent']) ?>, borderColor: C2, backgroundColor: C2, borderWidth: 2, pointRadius: 4, pointBorderColor: surface, pointBorderWidth: 2, pointStyle: 'rectRot', tension: .3 }
    ]},
    options: { maintainAspectRatio: false, interaction: tip, plugins: { legend: { position: 'top', align: 'end' } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
  <?php endif; ?>
})();
</script>
<?php $scripts = ob_get_clean(); ?>
