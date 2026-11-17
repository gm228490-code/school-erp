<?php $by = []; foreach ($rows as $r) $by[$r['class_name']][] = $r; ?>
<?php foreach ($by as $cls => $list): ?>
    <h6 class="fw-bold mt-3"><?= e($cls) ?></h6>
    <table class="table table-bordered table-sm">
        <thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Day')) ?></th><th><?= e(t('Time')) ?></th><th><?= e(t('Subject')) ?></th><th><?= e(t('Total Marks')) ?></th></tr></thead>
        <tbody><?php foreach ($list as $r): ?><tr><td><?= e(fmt_date($r['exam_date'])) ?></td><td><?= $r['exam_date'] ? e(date('l', strtotime($r['exam_date']))) : '' ?></td><td><?= e(fmt_time($r['start_time'])) ?></td><td><?= e($r['subject']) ?></td><td><?= (float)$r['total_marks'] ?></td></tr><?php endforeach; ?></tbody>
    </table>
<?php endforeach; ?>
<?php if (!$rows): ?><p><?= e(t('No schedule entered yet.')) ?></p><?php endif; ?>
<?php if ($exam['description']): ?><p class="small"><b><?= e(t('Instructions')) ?>:</b> <?= e($exam['description']) ?></p><?php endif; ?>
