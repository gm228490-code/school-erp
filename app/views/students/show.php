<?php
/** @var array $s */
$id = (int)$s['id'];
$attTotal = array_sum(array_map('intval', $att));
$attPresent = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0);
$attPct = $attTotal ? round($attPresent / $attTotal * 100, 1) : 0;
$due = array_sum(array_map(fn($i) => in_array($i['status'], ['unpaid', 'partial'], true) ? $i['net_amount'] - $i['paid_amount'] : 0, $invoices));
?>
<div class="profile-head mb-4">
    <div class="d-flex flex-wrap gap-4 align-items-center">
        <?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'])) ?>" class="avatar-lg" alt=""><?php else: ?><span class="avatar-lg avatar-ph"><?= e(mb_substr($s['first_name'], 0, 1)) ?></span><?php endif; ?>
        <div class="flex-grow-1">
            <h3 class="mb-1 fw-bold"><?= e(student_name($s)) ?></h3>
            <div class="opacity-75"><?= e($s['reg_no']) ?> · <?= e($s['class_name']) ?><?= $s['section_name'] ? ' - ' . e($s['section_name']) : '' ?> · <?= e(t('Roll')) ?> <?= e($s['roll_no'] ?: '-') ?></div>
            <div class="mt-2"><?= status_badge($s['status']) ?> <?php if ($s['route_name']): ?><span class="badge bg-light text-dark"><i class="bi bi-bus-front"></i> <?= e($s['route_name']) ?></span><?php endif; ?></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (can('students.edit')): ?><a href="<?= e(url('students/edit', ['id' => $id])) ?>" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> <?= e(t('Edit')) ?></a><?php endif; ?>
            <?php if (can('fees.collect')): ?><a href="<?= e(url('fees/collect', ['student_id' => $id])) ?>" class="btn btn-light btn-sm"><i class="bi bi-cash-coin"></i> <?= e(t('Collect Fee')) ?></a><?php endif; ?>
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i> <?= e(t('More')) ?></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" target="_blank" href="<?= e(url('students/print_profile', ['id' => $id])) ?>"><i class="bi bi-printer me-2"></i><?= e(t('Print Profile')) ?></a></li>
                    <li><a class="dropdown-item" target="_blank" href="<?= e(url('students/idcard', ['id' => $id])) ?>"><i class="bi bi-person-vcard me-2"></i><?= e(t('ID Card')) ?></a></li>
                    <?php if (can('fees.view')): ?><li><a class="dropdown-item" target="_blank" href="<?= e(url('fees/ledger', ['student_id' => $id])) ?>"><i class="bi bi-journal-text me-2"></i><?= e(t('Fee Ledger')) ?></a></li><?php endif; ?>
                    <li><a class="dropdown-item" target="_blank" href="<?= e(url('students/leaving_certificate', ['id' => $id])) ?>"><i class="bi bi-award me-2"></i><?= e(t('Leaving Certificate')) ?></a></li>
                    <?php if (can('students.promote')): ?><li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-modal="<?= e(url('students/status_form', ['id' => $id])) ?>" data-size="modal-md"><i class="bi bi-arrow-left-right me-2"></i><?= e(t('Transfer / Withdraw / Status')) ?></a></li><?php endif; ?>
                    <?php if (can('students.edit')): ?>
                        <li><a class="dropdown-item" href="#" data-post="<?= e(url('students/student_login', ['id' => $id])) ?>" data-confirm-text="<?= e(t('Create or reset the student portal login? A new password will be shown once.')) ?>"><i class="bi bi-key me-2"></i><?= e(t('Student portal login')) ?></a></li>
                        <?php if ($par): ?><li><a class="dropdown-item" href="#" data-post="<?= e(url('students/parent_login')) ?>" data-field-id="<?= (int)$par['id'] ?>" data-confirm-text="<?= e(t('Create or reset the parent portal login? A new password will be shown once.')) ?>"><i class="bi bi-key-fill me-2"></i><?= e(t('Parent portal login')) ?></a></li><?php endif; ?>
                    <?php endif; ?>
                    <?php if (can('students.delete')): ?><li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#" data-post="<?= e(url('students/delete', ['id' => $id])) ?>" data-confirm-text="<?= e(t('Permanently delete this student and all related records?')) ?>"><i class="bi bi-trash me-2"></i><?= e(t('Delete')) ?></a></li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-card tone-green"><span class="stat-icon"><i class="bi bi-calendar-check"></i></span><div><div class="stat-label"><?= e(t('Attendance')) ?></div><div class="stat-value"><?= $attPct ?>%</div><div class="stat-sub"><?= $attPresent ?> / <?= $attTotal ?> <?= e(t('days')) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card tone-red"><span class="stat-icon"><i class="bi bi-calendar-x"></i></span><div><div class="stat-label"><?= e(t('Absent')) ?></div><div class="stat-value"><?= (int)($att['absent'] ?? 0) ?></div><div class="stat-sub"><?= e(t('Leave')) ?>: <?= (int)($att['leave'] ?? 0) ?></div></div></div></div>
    <?php if (can('fees.view')): ?><div class="col-6 col-lg-3"><div class="card stat-card tone-amber"><span class="stat-icon"><i class="bi bi-hourglass-split"></i></span><div><div class="stat-label"><?= e(t('Fee Due')) ?></div><div class="stat-value"><?= e(money($due)) ?></div></div></div></div><?php endif; ?>
    <div class="col-6 col-lg-3"><div class="card stat-card tone-violet"><span class="stat-icon"><i class="bi bi-trophy"></i></span><div><div class="stat-label"><?= e(t('Last Result')) ?></div><div class="stat-value"><?= $results ? e($results[0]['percentage']) . '%' : '-' ?></div><div class="stat-sub"><?= $results ? e($results[0]['exam_name']) . ' · ' . e($results[0]['grade']) : '' ?></div></div></div></div>
</div>

<div class="card">
    <div class="card-header pb-0 border-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <?php foreach (['info' => 'Profile', 'att' => 'Attendance', 'fees' => 'Fees', 'results' => 'Results', 'docs' => 'Documents', 'history' => 'History'] as $k => $l):
                if ($k === 'fees' && !can('fees.view')) continue; ?>
                <li class="nav-item"><button class="nav-link <?= $k === 'info' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= $k ?>" type="button"><?= e(t($l)) ?></button></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="tab-info">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h6 class="form-section-title mb-3"><?= e(t('Personal Details')) ?></h6>
                    <dl class="kv">
                        <dt><?= e(t('Gender')) ?></dt><dd><?= e(t(ucfirst($s['gender']))) ?></dd>
                        <dt><?= e(t('Date of Birth')) ?></dt><dd><?= e(fmt_date($s['dob'])) ?><?= $s['dob'] ? ' (' . (int)date_diff(date_create($s['dob']), date_create())->y . ' ' . e(t('years')) . ')' : '' ?></dd>
                        <dt><?= e(t('B-Form / CNIC')) ?></dt><dd><?= e($s['b_form']) ?></dd>
                        <dt><?= e(t('Religion')) ?></dt><dd><?= e($s['religion']) ?></dd>
                        <dt><?= e(t('Nationality')) ?></dt><dd><?= e($s['nationality']) ?></dd>
                        <dt><?= e(t('Blood Group')) ?></dt><dd><?= e($s['blood_group']) ?></dd>
                        <dt><?= e(t('Phone')) ?></dt><dd><?= e($s['phone']) ?></dd>
                        <dt><?= e(t('Email')) ?></dt><dd><?= e($s['email']) ?></dd>
                        <dt><?= e(t('Address')) ?></dt><dd><?= e(trim($s['address'] . ', ' . $s['city'], ', ')) ?></dd>
                    </dl>
                    <h6 class="form-section-title mb-3 mt-4"><?= e(t('Academic')) ?></h6>
                    <dl class="kv">
                        <dt><?= e(t('Admission Date')) ?></dt><dd><?= e(fmt_date($s['admission_date'])) ?></dd>
                        <dt><?= e(t('Session')) ?></dt><dd><?= e($s['session_name']) ?></dd>
                        <dt><?= e(t('Previous School')) ?></dt><dd><?= e($s['previous_school']) ?> <?= $s['previous_class'] ? '(' . e($s['previous_class']) . ')' : '' ?></dd>
                        <dt><?= e(t('Transport')) ?></dt><dd><?= $s['route_name'] ? e($s['route_name']) . ' · ' . e(money($s['route_fee'])) . '/' . e(t('month')) : e(t('None')) ?></dd>
                        <dt><?= e(t('Portal Login')) ?></dt><dd><?= $login ? e($login['username']) . ' · ' . e(t('last login')) . ' ' . e(fmt_date($login['last_login'])) : '<span class="text-muted">' . e(t('Not created')) . '</span>' ?></dd>
                        <?php if ($s['status'] !== 'active'): ?><dt><?= e(t('Status Note')) ?></dt><dd><?= e(fmt_date($s['status_date'])) ?> — <?= e($s['status_reason']) ?></dd><?php endif; ?>
                    </dl>
                </div>
                <div class="col-lg-6">
                    <h6 class="form-section-title mb-3"><?= e(t('Parents / Guardian')) ?></h6>
                    <?php if ($par): ?>
                    <dl class="kv">
                        <dt><?= e(t("Father's Name")) ?></dt><dd class="fw-semibold"><?= e($par['father_name']) ?></dd>
                        <dt><?= e(t('CNIC')) ?></dt><dd><?= e($par['father_cnic']) ?></dd>
                        <dt><?= e(t('Phone')) ?></dt><dd><?= e($par['father_phone']) ?></dd>
                        <dt><?= e(t('Occupation')) ?></dt><dd><?= e($par['father_occupation']) ?></dd>
                        <dt><?= e(t("Mother's Name")) ?></dt><dd><?= e($par['mother_name']) ?></dd>
                        <dt><?= e(t("Mother's Phone")) ?></dt><dd><?= e($par['mother_phone']) ?></dd>
                        <?php if ($par['guardian_name']): ?><dt><?= e(t('Guardian')) ?></dt><dd><?= e($par['guardian_name']) ?> (<?= e($par['guardian_relation']) ?>) <?= e($par['guardian_phone']) ?></dd><?php endif; ?>
                        <dt><?= e(t('Email')) ?></dt><dd><?= e($par['email']) ?></dd>
                        <dt><?= e(t('Parent Portal')) ?></dt><dd><?= $par['username'] ? e($par['username']) : '<span class="text-muted">' . e(t('Not created')) . '</span>' ?></dd>
                    </dl>
                    <?php else: ?><p class="text-muted"><?= e(t('No parent linked.')) ?></p><?php endif; ?>
                    <?php if ($siblings): ?>
                        <h6 class="form-section-title mb-3 mt-4"><?= e(t('Siblings')) ?></h6>
                        <?php foreach ($siblings as $sb): ?><div><a href="<?= e(url('students/show', ['id' => $sb['id']])) ?>"><?= e(student_name($sb)) ?></a> <span class="text-muted small"><?= e($sb['reg_no'] . ' · ' . $sb['class_name']) ?></span></div><?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($books): ?>
                        <h6 class="form-section-title mb-3 mt-4"><?= e(t('Library')) ?></h6>
                        <?php foreach ($books as $b): ?><div class="small"><?= e($b['title']) ?> · <?= e(fmt_date($b['issue_date'])) ?> <?= status_badge($b['status']) ?></div><?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-att">
            <div class="d-flex flex-wrap gap-3 mb-3">
                <?php foreach (['present' => 'success', 'absent' => 'danger', 'leave' => 'info', 'late' => 'warning'] as $k => $c): ?>
                    <span class="badge text-bg-<?= $c ?> badge-soft fs-6"><?= e(t(ucfirst($k))) ?>: <?= (int)($att[$k] ?? 0) ?></span>
                <?php endforeach; ?>
            </div>
            <table class="table table-sm"><thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Day')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Remarks')) ?></th></tr></thead><tbody>
            <?php foreach ($att_recent as $a): ?><tr><td><?= e(fmt_date($a['att_date'])) ?></td><td><?= e(t(date('l', strtotime($a['att_date'])))) ?></td><td><?= status_badge($a['status']) ?></td><td><?= e($a['remarks']) ?></td></tr><?php endforeach; ?>
            <?php if (!$att_recent): ?><tr><td colspan="4" class="text-muted"><?= e(t('No attendance recorded.')) ?></td></tr><?php endif; ?>
            </tbody></table>
        </div>

        <?php if (can('fees.view')): ?>
        <div class="tab-pane fade" id="tab-fees">
            <?php if ($discounts): ?><div class="mb-3"><?php foreach ($discounts as $d): ?><span class="badge text-bg-info badge-soft me-1"><?= e($d['title']) ?>: <?= $d['value_type'] === 'percent' ? (float)$d['value'] . '%' : e(money($d['value'])) ?> <?= $d['cat_name'] ? '(' . e($d['cat_name']) . ')' : '' ?><?= $d['is_active'] ? '' : ' - ' . e(t('inactive')) ?></span><?php endforeach; ?></div><?php endif; ?>
            <h6 class="fw-semibold"><?= e(t('Invoices')) ?></h6>
            <div class="table-responsive mb-4"><table class="table table-sm"><thead><tr><th><?= e(t('Invoice')) ?></th><th><?= e(t('Title')) ?></th><th><?= e(t('Due Date')) ?></th><th class="text-end"><?= e(t('Net')) ?></th><th class="text-end"><?= e(t('Paid')) ?></th><th class="text-end"><?= e(t('Balance')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead><tbody>
            <?php foreach ($invoices as $i): ?><tr>
                <td><a href="<?= e(url('fees/invoice', ['id' => $i['id']])) ?>"><?= e($i['invoice_no']) ?></a></td><td><?= e($i['title']) ?></td><td><?= e(fmt_date($i['due_date'])) ?></td>
                <td class="text-end"><?= e(money($i['net_amount'])) ?></td><td class="text-end"><?= e(money($i['paid_amount'])) ?></td><td class="text-end fw-semibold"><?= e(money($i['status'] === 'cancelled' ? 0 : $i['net_amount'] - $i['paid_amount'])) ?></td><td><?= status_badge($i['status']) ?></td>
                <td><a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('fees/invoice', ['id' => $i['id'], 'print' => 1])) ?>"><i class="bi bi-printer"></i></a></td></tr>
            <?php endforeach; ?>
            <?php if (!$invoices): ?><tr><td colspan="8" class="text-muted"><?= e(t('No invoices.')) ?></td></tr><?php endif; ?>
            </tbody></table></div>
            <h6 class="fw-semibold"><?= e(t('Payment History')) ?></h6>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th><?= e(t('Receipt')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Invoice')) ?></th><th><?= e(t('Method')) ?></th><th class="text-end"><?= e(t('Amount')) ?></th><th></th></tr></thead><tbody>
            <?php foreach ($payments as $p): ?><tr><td><?= e($p['receipt_no']) ?></td><td><?= e(fmt_date($p['paid_on'])) ?></td><td><?= e($p['invoice_no']) ?></td><td><?= e(t(ucfirst($p['method']))) ?></td><td class="text-end"><?= e(money($p['amount'])) ?></td>
                <td><a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('fees/receipt', ['id' => $p['id']])) ?>"><i class="bi bi-receipt"></i></a></td></tr><?php endforeach; ?>
            <?php if (!$payments): ?><tr><td colspan="6" class="text-muted"><?= e(t('No payments.')) ?></td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
        <?php endif; ?>

        <div class="tab-pane fade" id="tab-results">
            <table class="table table-sm"><thead><tr><th><?= e(t('Exam')) ?></th><th><?= e(t('Session')) ?></th><th><?= e(t('Marks')) ?></th><th>%</th><th><?= e(t('Grade')) ?></th><th><?= e(t('Position')) ?></th><th><?= e(t('Result')) ?></th><th></th></tr></thead><tbody>
            <?php foreach ($results as $r): ?><tr><td><?= e($r['exam_name']) ?></td><td><?= e($r['session_name']) ?></td><td><?= (float)$r['obtained_marks'] ?> / <?= (float)$r['total_marks'] ?></td><td><?= e($r['percentage']) ?></td><td class="fw-bold"><?= e($r['grade']) ?></td><td><?= e(ResultService::ordinal($r['position'] ? (int)$r['position'] : null)) ?></td><td><?= status_badge($r['result_status']) ?></td>
                <td><a class="btn btn-sm btn-light-soft" target="_blank" href="<?= e(url('exams/report_card', ['exam_id' => $r['exam_id'], 'student_id' => $id])) ?>"><i class="bi bi-file-earmark-text"></i></a></td></tr><?php endforeach; ?>
            <?php if (!$results): ?><tr><td colspan="8" class="text-muted"><?= e(t('No results yet.')) ?></td></tr><?php endif; ?>
            </tbody></table>
        </div>

        <div class="tab-pane fade" id="tab-docs">
            <?php if (can('students.edit')): ?>
            <form method="post" action="<?= e(url('students/upload_document', ['id' => $id])) ?>" enctype="multipart/form-data" class="row g-2 mb-3 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-5"><label class="form-label"><?= e(t('Title')) ?></label><input name="title" class="form-control form-control-sm" required maxlength="150"></div>
                <div class="col-md-5"><label class="form-label"><?= e(t('File')) ?></label><input type="file" name="file" class="form-control form-control-sm" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> <?= e(t('Upload')) ?></button></div>
            </form>
            <?php endif; ?>
            <table class="table table-sm"><thead><tr><th><?= e(t('Title')) ?></th><th><?= e(t('File')) ?></th><th><?= e(t('Uploaded')) ?></th><th></th></tr></thead><tbody>
            <?php foreach ($docs as $d): ?><tr><td><?= e($d['title']) ?></td><td><a target="_blank" href="<?= e(upload_url($d['file_path'])) ?>"><i class="bi bi-paperclip"></i> <?= e($d['original_name'] ?: t('View')) ?></a></td><td><?= e(fmt_date($d['created_at'])) ?></td>
                <td class="text-end"><?php if (can('students.edit')): ?><button class="btn btn-sm btn-outline-danger" data-post="<?= e(url('students/delete_document')) ?>" data-field-id="<?= (int)$d['id'] ?>" data-remove-row><i class="bi bi-trash"></i></button><?php endif; ?></td></tr><?php endforeach; ?>
            <?php if (!$docs): ?><tr><td colspan="4" class="text-muted"><?= e(t('No documents uploaded.')) ?></td></tr><?php endif; ?>
            </tbody></table>
        </div>

        <div class="tab-pane fade" id="tab-history">
            <div class="list-feed">
            <?php foreach ($history as $h): ?>
                <div class="feed-item"><span class="feed-icon"><i class="bi <?= ['admission' => 'bi-person-plus', 'promotion' => 'bi-arrow-up-circle', 'transfer' => 'bi-arrow-left-right', 'withdrawn' => 'bi-box-arrow-left', 'graduated' => 'bi-mortarboard'][$h['action']] ?? 'bi-clock-history' ?>"></i></span>
                    <div><div class="fw-semibold"><?= e(t(ucwords(str_replace('_', ' ', $h['action'])))) ?> <span class="text-muted small">· <?= e($h['session_name']) ?> <?= e($h['class_name']) ?></span></div><div class="small"><?= e($h['details']) ?></div><div class="small text-muted"><?= e(fmt_date($h['created_at'], 'd-M-Y h:i A')) ?> <?= $h['by_name'] ? '· ' . e($h['by_name']) : '' ?></div></div></div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php $scripts = '<script>if (location.hash) { const b = document.querySelector(\'[data-bs-target="#tab-\' + location.hash.slice(1) + \'"]\'); if (b) bootstrap.Tab.getOrCreateInstance(b).show(); }</script>'; ?>
