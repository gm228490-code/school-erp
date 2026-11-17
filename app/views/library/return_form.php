<form method="post" action="<?= e(url('library/return')) ?>" data-ajax novalidate>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$bi['id'] ?>">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-box-arrow-in-left me-2 text-primary"></i><?= e(t('Return Book')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p class="mb-2"><b><?= e($bi['title']) ?></b><br><span class="small text-muted"><?= e($bi['member']) ?> · <?= e(t('Due')) ?> <?= e(fmt_date($bi['due_date'])) ?></span></p>
        <?php if ($late): ?><div class="alert alert-warning py-2 small"><?= sprintf(e(t('Returned %d day(s) late. Fine at %s per day.')), $late, e(money(setting('library_fine_per_day', '10')))) ?></div><?php endif; ?>
        <div class="row g-3">
            <?= form_field('return_date', ['label' => 'Return Date', 'type' => 'date'], date('Y-m-d')) ?>
            <?= form_field('fine', ['label' => 'Fine', 'type' => 'decimal'], $fine) ?>
            <?= form_field('fine_paid', ['label' => 'Fine collected now (recorded as income)', 'type' => 'checkbox', 'col' => 12], $fine > 0 ? 1 : 0) ?>
            <?= form_field('lost', ['label' => 'Book lost (reduce stock)', 'type' => 'checkbox', 'col' => 12]) ?>
            <?= form_field('remarks', ['label' => 'Remarks', 'col' => 12]) ?>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(t('Cancel')) ?></button><button class="btn btn-success"><?= e(t('Confirm')) ?></button></div>
</form>
