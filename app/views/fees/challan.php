<?php $copies = array_slice([t('Parent Copy'), t('School Copy'), t('Bank Copy')], 0, max(1, min(3, (int)setting('receipt_copies', '2')))); ?>
<div class="row g-2">
    <?php foreach ($copies as $copy): ?><div class="col"><?php include __DIR__ . '/_challan_copy.php'; ?></div><?php endforeach; ?>
</div>
