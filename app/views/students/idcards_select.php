<?php partial('page_head', ['title' => t('Student ID Cards'), 'icon' => 'bi-person-vcard', 'crumbs' => [t('Students') => url('students'), t('ID Cards') => null]]); ?>
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="get" target="_blank" class="row g-3 align-items-end">
            <input type="hidden" name="r" value="students/idcards">
            <div class="col-md-5"><label class="form-label"><?= e(t('Class')) ?></label><select name="class_id" id="idClass" class="form-select" required><?= options(class_options(), null, t('-- Select --')) ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= e(t('Section')) ?></label><select name="section_id" class="form-select" data-sections-for="#idClass"><option value=""><?= e(t('All Sections')) ?></option></select></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-printer"></i> <?= e(t('Generate')) ?></button></div>
        </form>
        <p class="small text-muted mt-3 mb-0"><?= e(t('Cards are CR80 size (86 × 54 mm), 2 per row. Colour, note and validity are configured in Settings → ID Card.')) ?></p>
    </div>
</div>
