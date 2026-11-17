<?php $cats = DB::pairs('SELECT id, name FROM fee_categories ORDER BY id'); ?>
<form method="post" action="<?= e(url('fees/create_invoice')) ?>" data-ajax novalidate>
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt me-2 text-primary"></i><?= e(t('New Invoice')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label"><?= e(t('Student')) ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" data-student-search data-target="#ciStudent" placeholder="<?= e(t('Type name or Reg No…')) ?>" value="<?= $studentId ? e(DB::val("SELECT CONCAT(first_name,' ',IFNULL(last_name,''),' (',reg_no,')') FROM students WHERE id=?", [$studentId])) : '' ?>">
                <input type="hidden" name="student_id" id="ciStudent" value="<?= (int)$studentId ?>">
                <div class="invalid-feedback" data-error-for="student_id"></div>
            </div>
            <?= form_field('title', ['label' => 'Invoice Title', 'rules' => 'required|max:150', 'col' => 8, 'placeholder' => 'e.g. Annual Fund 2026 / Exam Fee'], '') ?>
            <?= form_field('due_date', ['label' => 'Due Date', 'type' => 'date', 'rules' => 'required', 'col' => 4], date('Y-m-d', strtotime('+10 days'))) ?>
            <div class="col-12">
                <table class="table table-sm" id="ciItems">
                    <thead><tr><th><?= e(t('Category')) ?></th><th><?= e(t('Description')) ?></th><th style="width:130px"><?= e(t('Amount')) ?></th><th style="width:120px"><?= e(t('Discount')) ?></th></tr></thead>
                    <tbody>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <tr><td><select name="item_cat[]" class="form-select form-select-sm"><?= options($cats, null, t('-- Other --')) ?></select></td><td><input name="item_desc[]" class="form-control form-control-sm" maxlength="150"></td>
                            <td><input type="number" step="0.01" min="0" name="item_amount[]" class="form-control form-control-sm"></td><td><input type="number" step="0.01" min="0" name="item_discount[]" class="form-control form-control-sm" value="0"></td></tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
                <div class="invalid-feedback d-block" data-error-for="item_amount"></div>
            </div>
            <?= form_field('remarks', ['label' => 'Remarks', 'col' => 12, 'rules' => 'max:255']) ?>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><i class="bi bi-check2"></i> <?= e(t('Create Invoice')) ?></button></div>
</form>
