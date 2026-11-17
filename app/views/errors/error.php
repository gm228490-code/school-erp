<?php $title = 'Error ' . $code; ?>
<div class="d-flex align-items-center justify-content-center py-5" style="min-height:60vh">
    <div class="text-center">
        <div class="display-1 fw-bold text-primary opacity-75"><?= (int)$code ?></div>
        <h4 class="mb-3"><?= $code == 403 ? e(t('Access denied')) : ($code == 404 ? e(t('Page not found')) : e(t('Something went wrong'))) ?></h4>
        <p class="text-muted"><?= e($message) ?></p>
        <a href="<?= e(Auth::isPortal() ? url('portal') : url('dashboard')) ?>" class="btn btn-primary"><i class="bi bi-house"></i> <?= e(t('Go to Dashboard')) ?></a>
    </div>
</div>
