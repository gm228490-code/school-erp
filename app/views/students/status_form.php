<div class="modal-header">
    <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2 text-primary"></i><?= e(t('Transfer / Status')) ?> — <?= e(student_name($s)) ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <ul class="nav nav-pills mb-3 small">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#st-status" type="button"><?= e(t('Change Status')) ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#st-transfer" type="button"><?= e(t('Transfer Class / Section')) ?></button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="st-status">
            <form method="post" action="<?= e(url('students/change_status', ['id' => $s['id']])) ?>" data-ajax>
                <?= csrf_field() ?><input type="hidden" name="mode" value="status">
                <div class="row g-3">
                    <?= form_field('status', ['label' => 'New Status', 'type' => 'select', 'rules' => 'required', 'options' => ['active' => 'Active', 'withdrawn' => 'Withdrawn (left school)', 'transferred' => 'Transferred to another school', 'suspended' => 'Suspended', 'graduated' => 'Graduated / Passed out']], $s['status']) ?>
                    <?= form_field('status_date', ['label' => 'Effective Date', 'type' => 'date', 'rules' => 'required'], date('Y-m-d')) ?>
                    <?= form_field('reason', ['label' => 'Reason / Remarks', 'type' => 'textarea', 'col' => 12, 'rows' => 2, 'rules' => 'max:255']) ?>
                </div>
                <div class="alert alert-light border small mt-3 mb-3"><?= e(t('Withdrawn / transferred students are removed from attendance, fee generation and class lists, but their history is kept. Their portal login is disabled.')) ?></div>
                <button class="btn btn-primary"><i class="bi bi-check2"></i> <?= e(t('Update Status')) ?></button>
            </form>
        </div>
        <div class="tab-pane fade" id="st-transfer">
            <form method="post" action="<?= e(url('students/change_status', ['id' => $s['id']])) ?>" data-ajax>
                <?= csrf_field() ?><input type="hidden" name="mode" value="transfer_section">
                <div class="row g-3">
                    <?= form_field('class_id', ['label' => 'Class', 'type' => 'select', 'rules' => 'required', 'options' => DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), 'attrs' => 'id="trClass"'], $s['class_id']) ?>
                    <?= form_field('section_id', ['label' => 'Section', 'type' => 'select', 'options' => DB::pairs('SELECT id,name FROM sections WHERE class_id=?', [$s['class_id']]), 'attrs' => 'data-sections-for="#trClass"'], $s['section_id']) ?>
                    <?= form_field('reason', ['label' => 'Reason', 'col' => 12]) ?>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="override_capacity" value="1" id="oc2"><label class="form-check-label small" for="oc2"><?= e(t('Allow over capacity')) ?></label></div></div>
                </div>
                <button class="btn btn-primary mt-3"><i class="bi bi-arrow-left-right"></i> <?= e(t('Transfer')) ?></button>
            </form>
        </div>
    </div>
</div>
