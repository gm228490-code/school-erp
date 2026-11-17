<?php
$id = (int)$e['id'];
$attTot = array_sum(array_map('intval', $att));
$attP = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0);
$days = days_of_week();
?>
<div class="profile-head mb-4">
    <div class="d-flex flex-wrap gap-4 align-items-center">
        <?php if ($e['photo']): ?><img src="<?= e(upload_url($e['photo'])) ?>" class="avatar-lg" alt=""><?php else: ?><span class="avatar-lg avatar-ph"><?= e(mb_substr($e['name'], 0, 1)) ?></span><?php endif; ?>
        <div class="flex-grow-1">
            <h3 class="fw-bold mb-1"><?= e($e['name']) ?></h3>
            <div class="opacity-75"><?= e($e['employee_no']) ?> · <?= e($e['desig'] ?: t($type === 'teacher' ? 'Teacher' : 'Staff')) ?> <?= $e['dept'] ? '· ' . e($e['dept']) : '' ?></div>
            <div class="mt-2"><?= status_badge($e['status']) ?></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (can("$perm.edit")): ?><button class="btn btn-light btn-sm" data-modal="<?= e(url('employees/form', ['type' => $type, 'id' => $id])) ?>" data-size="modal-xl"><i class="bi bi-pencil"></i> <?= e(t('Edit')) ?></button><?php endif; ?>
            <a class="btn btn-light btn-sm" target="_blank" href="<?= e(url('employees/idcard', ['type' => $type, 'id' => $id])) ?>"><i class="bi bi-person-vcard"></i> <?= e(t('ID Card')) ?></a>
            <?php if (can("$perm.edit")): ?><button class="btn btn-light btn-sm" data-post="<?= e(url('employees/login', ['type' => $type, 'id' => $id])) ?>" data-confirm-text="<?= e(t('Create or reset the login? A new password will be shown once.')) ?>"><i class="bi bi-key"></i> <?= e(t('Login')) ?></button><?php endif; ?>
            <?php if (can("$perm.delete")): ?><button class="btn btn-light btn-sm text-danger" data-post="<?= e(url('employees/delete', ['type' => $type, 'id' => $id])) ?>" data-confirm-text="<?= e(t('Delete this record permanently?')) ?>"><i class="bi bi-trash"></i></button><?php endif; ?>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card mb-4"><div class="card-header"><?= e(t('Profile')) ?></div><div class="card-body">
            <dl class="kv">
                <dt><?= e(t("Father's Name")) ?></dt><dd><?= e($e['father_name']) ?></dd>
                <dt><?= e(t('Gender')) ?></dt><dd><?= e(t(ucfirst($e['gender']))) ?></dd>
                <dt><?= e(t('Date of Birth')) ?></dt><dd><?= e(fmt_date($e['dob'])) ?></dd>
                <dt><?= e(t('CNIC')) ?></dt><dd><?= e($e['cnic']) ?></dd>
                <dt><?= e(t('Phone')) ?></dt><dd><?= e($e['phone']) ?></dd>
                <dt><?= e(t('Email')) ?></dt><dd><?= e($e['email']) ?></dd>
                <dt><?= e(t('Address')) ?></dt><dd><?= e($e['address']) ?></dd>
                <dt><?= e(t('Qualification')) ?></dt><dd><?= e($e['qualification']) ?></dd>
                <dt><?= e(t('Specialization')) ?></dt><dd><?= e($e['specialization']) ?></dd>
                <dt><?= e(t('Experience')) ?></dt><dd><?= (float)$e['experience_years'] ?> <?= e(t('years')) ?></dd>
                <dt><?= e(t('Joining Date')) ?></dt><dd><?= e(fmt_date($e['joining_date'])) ?></dd>
                <?php if (can('finance.salary')): ?>
                <dt><?= e(t('Basic Salary')) ?></dt><dd><?= e(money($e['basic_salary'])) ?></dd>
                <dt><?= e(t('Allowances')) ?></dt><dd><?= e(money($e['allowances'])) ?></dd>
                <dt><?= e(t('Bank')) ?></dt><dd><?= e(trim($e['bank_name'] . ' ' . $e['bank_account'])) ?></dd>
                <?php endif; ?>
                <dt><?= e(t('Login')) ?></dt><dd><?= $e['username'] ? e($e['username']) . ' (' . e(t($e['role_name'])) . ')' : '<span class="text-muted">' . e(t('Not created')) . '</span>' ?></dd>
            </dl>
        </div></div>
        <div class="card"><div class="card-header"><?= e(t('Attendance (current session)')) ?></div><div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (['present' => 'success', 'absent' => 'danger', 'leave' => 'info', 'late' => 'warning'] as $k => $c): ?><span class="badge text-bg-<?= $c ?> badge-soft fs-6"><?= e(t(ucfirst($k))) ?>: <?= (int)($att[$k] ?? 0) ?></span><?php endforeach; ?>
            </div>
            <div class="mt-2 small text-muted"><?= e(t('Attendance')) ?>: <?= $attTot ? round($attP / $attTot * 100, 1) : 0 ?>%</div>
        </div></div>
    </div>
    <div class="col-lg-7">
        <?php if ($type === 'teacher'): ?>
        <div class="card mb-4"><div class="card-header"><?= e(t('Assigned Classes & Subjects')) ?></div><div class="card-body">
            <?php if ($classTeacher): ?><p class="mb-2"><b><?= e(t('Class Teacher of')) ?>:</b> <?= e(implode(', ', array_map(fn($c) => $c['class_name'] . ' ' . $c['section_name'], $classTeacher))) ?></p><?php endif; ?>
            <div class="d-flex flex-wrap gap-2"><?php foreach ($subjects as $s): ?><span class="badge text-bg-primary badge-soft"><?= e($s['subject']) ?> · <?= e($s['class_name'] . ' ' . $s['section_name']) ?></span><?php endforeach; ?></div>
            <?php if (!$subjects && !$classTeacher): ?><p class="text-muted mb-0"><?= e(t('No classes assigned yet. Use Academics → Assign Subjects.')) ?></p><?php endif; ?>
        </div></div>
        <div class="card mb-4"><div class="card-header d-flex justify-content-between"><span><?= e(t('Weekly Timetable')) ?></span><a target="_blank" href="<?= e(url('timetable/teacher', ['teacher_id' => $id, 'print' => 1])) ?>" class="small"><i class="bi bi-printer"></i> <?= e(t('Print')) ?></a></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= e(t('Day')) ?></th><th><?= e(t('Period')) ?></th><th><?= e(t('Class')) ?></th><th><?= e(t('Subject')) ?></th></tr></thead><tbody>
            <?php foreach ($timetable as $t): ?><tr><td><?= e(t($days[$t['day_of_week']])) ?></td><td><?= e($t['period']) ?> <span class="text-muted small"><?= e(fmt_time($t['start_time'])) ?></span></td><td><?= e($t['class_name'] . ' ' . $t['section_name']) ?></td><td><?= e($t['subject']) ?></td></tr><?php endforeach; ?>
            <?php if (!$timetable): ?><tr><td colspan="4" class="text-muted"><?= e(t('No timetable entries.')) ?></td></tr><?php endif; ?>
            </tbody></table></div></div>
        <div class="card mb-4"><div class="card-header"><?= e(t('Performance Records')) ?></div>
            <ul class="list-group list-group-flush">
            <?php foreach ($performance as $pf): ?><li class="list-group-item"><div class="d-flex justify-content-between"><b><?= e($pf['category']) ?> <span class="text-muted small"><?= e($pf['period']) ?></span></b><span><?= str_repeat('<i class="bi bi-star-fill text-warning"></i>', (int)$pf['rating']) ?></span></div><div class="small text-muted"><?= e(fmt_date($pf['review_date'])) ?> — <?= e($pf['remarks']) ?></div></li><?php endforeach; ?>
            <?php if (!$performance): ?><li class="list-group-item text-muted small"><?= e(t('No reviews yet.')) ?></li><?php endif; ?>
            </ul></div>
        <?php endif; ?>
        <div class="card mb-4"><div class="card-header"><?= e(t('Leave History')) ?></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= e(t('Type')) ?></th><th><?= e(t('From')) ?></th><th><?= e(t('To')) ?></th><th><?= e(t('Days')) ?></th><th><?= e(t('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($leaves as $l): ?><tr><td><?= e(t(ucfirst($l['leave_type']))) ?></td><td><?= e(fmt_date($l['from_date'])) ?></td><td><?= e(fmt_date($l['to_date'])) ?></td><td><?= (int)$l['days'] ?></td><td><?= status_badge($l['status']) ?></td></tr><?php endforeach; ?>
            <?php if (!$leaves): ?><tr><td colspan="5" class="text-muted"><?= e(t('No leaves.')) ?></td></tr><?php endif; ?>
            </tbody></table></div></div>
        <?php if ($salaries): ?>
        <div class="card mb-4"><div class="card-header"><?= e(t('Salary History')) ?></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= e(t('Month')) ?></th><th><?= e(t('Slip')) ?></th><th class="text-end"><?= e(t('Net')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead><tbody>
            <?php foreach ($salaries as $s): ?><tr><td><?= e(fmt_month($s['salary_month'])) ?></td><td><?= e($s['slip_no']) ?></td><td class="text-end"><?= e(money($s['net_salary'])) ?></td><td><?= status_badge($s['status']) ?></td><td><a target="_blank" class="btn btn-sm btn-light-soft" href="<?= e(url('finance/slip', ['id' => $s['id']])) ?>"><i class="bi bi-printer"></i></a></td></tr><?php endforeach; ?>
            </tbody></table></div></div>
        <?php endif; ?>
        <div class="card"><div class="card-header"><?= e(t('Documents')) ?></div><div class="card-body">
            <?php if (can("$perm.edit")): ?>
            <form method="post" action="<?= e(url('employees/upload_document', ['type' => $type, 'id' => $id])) ?>" enctype="multipart/form-data" class="row g-2 mb-3">
                <?= csrf_field() ?>
                <div class="col-md-5"><input name="title" class="form-control form-control-sm" placeholder="<?= e(t('Title (e.g. CNIC copy, Degree)')) ?>" required></div>
                <div class="col-md-5"><input type="file" name="file" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><?= e(t('Upload')) ?></button></div>
            </form>
            <?php endif; ?>
            <?php foreach ($docs as $d): ?><div class="d-flex justify-content-between border-bottom py-1 small"><a target="_blank" href="<?= e(upload_url($d['file_path'])) ?>"><i class="bi bi-paperclip"></i> <?= e($d['title']) ?></a>
                <?php if (can("$perm.edit")): ?><button class="btn btn-sm btn-link text-danger p-0" data-post="<?= e(url('employees/delete_document', ['type' => $type])) ?>" data-field-doc_id="<?= (int)$d['id'] ?>"><i class="bi bi-trash"></i></button><?php endif; ?></div><?php endforeach; ?>
            <?php if (!$docs): ?><p class="text-muted small mb-0"><?= e(t('No documents uploaded.')) ?></p><?php endif; ?>
        </div></div>
    </div>
</div>
