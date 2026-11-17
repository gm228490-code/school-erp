<?php partial('page_head', ['title' => t('Import Students'), 'icon' => 'bi-upload', 'crumbs' => [t('Students') => url('students'), t('Import') => null]]); ?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <form method="post" enctype="multipart/form-data" action="<?= e(url('students/import')) ?>">
                <?= csrf_field() ?>
                <div class="dropzone-like mb-3"><i class="bi bi-filetype-csv fs-1 d-block"></i>
                    <input type="file" name="csv" accept=".csv,text/csv" class="form-control mt-2" required></div>
                <button class="btn btn-primary"><i class="bi bi-upload"></i> <?= e(t('Import')) ?></button>
                <a href="<?= e(url('students/import', ['template' => 1])) ?>" class="btn btn-outline-secondary"><i class="bi bi-download"></i> <?= e(t('Download template')) ?></a>
            </form>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><?= e(t('Instructions')) ?></div><div class="card-body small">
            <ol class="mb-0">
                <li><?= e(t('Download the CSV template and fill one student per row (Excel → Save As → CSV UTF-8).')) ?></li>
                <li><?= e(t('Required: first_name, gender (male/female), class (exact class name), father_name.')) ?></li>
                <li><?= e(t('Dates must be YYYY-MM-DD. CNIC/B-Form format: 00000-0000000-0.')) ?></li>
                <li><?= e(t('Students with the same father CNIC are linked to one parent record.')) ?></li>
                <li><?= e(t('Registration numbers are generated automatically. Rows with errors are skipped and reported.')) ?></li>
            </ol>
        </div></div>
    </div>
</div>
