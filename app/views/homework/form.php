<?php $cid = (int)($h['class_id'] ?? 0); ?>
<form method="post" action="<?= e(url('homework/save')) ?>" enctype="multipart/form-data" data-ajax novalidate>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($h['id'] ?? 0) ?>">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-journal-text me-2 text-primary"></i><?= e($h ? t('Edit Homework') : t('New Homework')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <?= form_field('class_id', ['label' => 'Class', 'type' => 'select', 'rules' => 'required', 'options' => class_options(), 'col' => 4, 'attrs' => 'id="hwClass"'], $cid ?: null) ?>
        <?= form_field('section_id', ['label' => 'Section', 'type' => 'select', 'options' => $cid ? section_options($cid) : [], 'col' => 4, 'placeholder' => Auth::scoped() ? '-- Select --' : 'All sections', 'attrs' => 'data-sections-for="#hwClass"'], $h['section_id'] ?? null) ?>
        <?= form_field('subject_id', ['label' => 'Subject', 'type' => 'select', 'rules' => 'required', 'options' => subject_options($cid ?: null), 'col' => 4, 'attrs' => 'data-subjects-for="#hwClass"'], $h['subject_id'] ?? null) ?>
        <?= form_field('title', ['label' => 'Title', 'rules' => 'required|max:200', 'col' => 12], $h['title'] ?? '') ?>
        <?= form_field('description', ['label' => 'Description / Instructions', 'type' => 'textarea', 'rows' => 4, 'col' => 12], $h['description'] ?? '') ?>
        <?= form_field('assign_date', ['label' => 'Assign Date', 'type' => 'date', 'rules' => 'required', 'col' => 4], $h['assign_date'] ?? date('Y-m-d')) ?>
        <?= form_field('due_date', ['label' => 'Submission Deadline', 'type' => 'date', 'rules' => 'required', 'col' => 4], $h['due_date'] ?? date('Y-m-d', strtotime('+2 days'))) ?>
        <?= form_field('max_marks', ['label' => 'Max Marks (optional)', 'type' => 'decimal', 'col' => 4], $h['max_marks'] ?? '') ?>
        <?= form_field('attachment', ['label' => 'Attachment (worksheet, PDF, image)', 'type' => 'file', 'col' => 12], $h['attachment'] ?? null) ?>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><i class="bi bi-check2"></i> <?= e(t('Save')) ?></button></div>
</form>
