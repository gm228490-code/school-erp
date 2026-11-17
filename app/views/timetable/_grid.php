<table class="table table-bordered tt-table mb-0">
    <thead><tr><th><?= e(t('Day')) ?></th><?php foreach ($periods as $p): ?><th><?= e($p['name']) ?><div class="fw-normal small"><?= e(fmt_time($p['start_time'])) ?> – <?= e(fmt_time($p['end_time'])) ?></div></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($days as $d => $dn): ?>
        <tr><th><?= e(t($dn)) ?></th>
        <?php foreach ($periods as $p): $c = $grid[$d][$p['id']] ?? null; ?>
            <?php if ($p['is_break']): ?><td class="tt-break"><?= e($p['name']) ?></td>
            <?php else: ?><td><?php if ($c): ?><div class="tt-cell"><div class="fw-semibold"><?= e($c['subject'] ?? '') ?></div>
                <div class="small"><?= !empty($isTeacher) || isset($c['class_name']) ? e(($c['class_name'] ?? '') . ' ' . ($c['section_name'] ?? '')) : e($c['teacher'] ?? '') ?></div><?= $c['room'] ? '<div class="small text-muted">' . e($c['room']) . '</div>' : '' ?></div><?php endif; ?></td><?php endif; ?>
        <?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody>
</table>
