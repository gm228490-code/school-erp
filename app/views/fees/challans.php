<?php foreach ($list as $n => $row): $inv = $row['inv']; $items = $row['items']; ?>
    <div class="row g-2 <?= $n % 2 === 1 ? 'page-break' : '' ?>" style="margin-bottom:10px">
        <?php foreach ([t('Parent Copy'), t('School Copy')] as $copy): ?><div class="col"><?php include __DIR__ . '/_challan_copy.php'; ?></div><?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php if (!$list): ?><p><?= e(t('No unpaid invoices for the selected filters.')) ?></p><?php endif; ?>
