<form method="post" action="<?= e(url('library/issue')) ?>" data-ajax novalidate>
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-box-arrow-right me-2 text-primary"></i><?= e(t('Issue Book')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <?= form_field('book_id', ['label' => 'Book', 'type' => 'select', 'rules' => 'required', 'col' => 12, 'options' => DB::pairs("SELECT id, CONCAT(title, IFNULL(CONCAT(' - ', author),''), ' [', available, ' available]') FROM books WHERE available>0 ORDER BY title")], $bookId) ?>
        <div class="col-12"><label class="form-label"><?= e(t('Member Type')) ?></label>
            <div class="btn-group w-100" role="group">
                <?php foreach (['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'] as $k => $l): ?>
                    <input type="radio" class="btn-check" name="member_type" id="mt_<?= $k ?>" value="<?= $k ?>" <?= $k === 'student' ? 'checked' : '' ?> onchange="document.getElementById('memStu').classList.toggle('d-none',this.value!=='student');document.getElementById('memEmp').classList.toggle('d-none',this.value==='student')">
                    <label class="btn btn-outline-primary" for="mt_<?= $k ?>"><?= e(t($l)) ?></label>
                <?php endforeach; ?>
            </div></div>
        <div class="col-12" id="memStu"><label class="form-label"><?= e(t('Student')) ?></label><div><input class="form-control" data-student-search data-target="#libStu" placeholder="<?= e(t('Type name or Reg No…')) ?>" autocomplete="off"></div><input type="hidden" name="student_id" id="libStu"><div class="invalid-feedback d-block" data-error-for="student_id"></div></div>
        <div class="col-12 d-none" id="memEmp"><?= form_field('employee', ['label' => 'Teacher / Staff', 'type' => 'select', 'options' => employee_options(), 'col' => 12]) ?></div>
        <?= form_field('issue_date', ['label' => 'Issue Date', 'type' => 'date'], date('Y-m-d')) ?>
        <?= form_field('due_date', ['label' => 'Due Date', 'type' => 'date', 'rules' => 'required'], date('Y-m-d', strtotime('+' . (int)setting('library_loan_days', '14') . ' days'))) ?>
        <?= form_field('remarks', ['label' => 'Remarks', 'col' => 12]) ?>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Issue')) ?></button></div>
</form>
