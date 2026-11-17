<?php partial('page_head', ['title' => t('Welcome') . ', ' . Auth::user()['name'], 'icon' => 'bi-house']); ?>
<div class="card"><div class="card-body">
    <p class="mb-0 text-muted"><?= e(t('Use the menu on the left to open the modules available to your role.')) ?></p>
</div></div>
