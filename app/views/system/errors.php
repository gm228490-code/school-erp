<?php partial('page_head', ['title' => t('Error Log'), 'icon' => 'bi-bug', 'crumbs' => [t('Administration') => null, t('Error Log') => null],
    'actions' => Auth::isSuper() ? '<form method="post" action="' . e(url('system/clear_errors')) . '" data-confirm="' . e(t('Clear the error log?')) . '">' . csrf_field() . '<button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> ' . e(t('Clear')) . '</button></form>' : '']); ?>
<div class="card"><div class="card-header small"><?= e(t('Latest entries first')) ?> · <?= number_format($size / 1024, 1) ?> KB</div>
    <div class="card-body p-0"><pre class="mb-0 p-3 small" style="max-height:70vh;overflow:auto;white-space:pre-wrap"><?= $lines ? e(implode("\n", $lines)) : e(t('No errors logged.')) ?></pre></div></div>
