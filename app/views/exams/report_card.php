<?php foreach ($cards as $i => $c): $r = $c['res']; $att = $c['att']; $attT = array_sum(array_map('intval', $att)); $attP = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0); ?>
<div class="<?= $i < count($cards) - 1 ? 'page-break' : '' ?>" style="border:2px solid #1e3a8a;padding:14px;margin-bottom:14px">
    <div class="letterhead">
        <?php if (setting('school_logo')): ?><img src="<?= e(upload_url(setting('school_logo'))) ?>" alt="" class="lh-logo"><?php endif; ?>
        <div class="lh-text"><h2><?= e(setting('school_name')) ?></h2><div><?= e(setting('school_address')) ?></div><div><?= e(setting('school_phone')) ?> <?= e(setting('school_email')) ?></div></div>
        <?php if ($r['photo']): ?><img src="<?= e(upload_url($r['photo'])) ?>" alt="" style="width:70px;height:84px;object-fit:cover;border:1px solid #999"><?php endif; ?>
    </div>
    <h4 class="doc-title"><?= e(t('Progress Report')) ?> — <?= e($exam['name']) ?></h4>
    <div class="doc-subtitle"><?= e(t('Session')) ?> <?= e($exam['session_name']) ?></div>
    <div class="kv-print">
        <b><?= e(t('Student')) ?>:</b><span><?= e(student_name($r)) ?></span><b><?= e(t('Reg No')) ?>:</b><span><?= e($r['reg_no']) ?></span>
        <b><?= e(t('Father')) ?>:</b><span><?= e($r['father_name']) ?></span><b><?= e(t('Class')) ?>:</b><span><?= e($r['class_name'] . ' ' . $r['section_name']) ?></span>
        <b><?= e(t('Roll No')) ?>:</b><span><?= e($r['roll_no']) ?></span><b><?= e(t('Date of Birth')) ?>:</b><span><?= e(fmt_date($r['dob'])) ?></span>
    </div>
    <table class="table table-bordered table-sm text-center">
        <thead><tr><th class="text-start"><?= e(t('Subject')) ?></th><th><?= e(t('Total')) ?></th><th><?= e(t('Pass')) ?></th><th><?= e(t('Obtained')) ?></th><th>%</th><th><?= e(t('Grade')) ?></th><th><?= e(t('Remarks')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($c['subjects'] as $s): $o = $s['is_absent'] ? 0 : (float)$s['obtained_marks']; $pct = $s['total_marks'] > 0 ? $o / $s['total_marks'] * 100 : 0; $g = ResultService::gradeFor($pct);
            $fail = $s['obtained_marks'] === null || $s['is_absent'] || $o < (float)$s['pass_marks']; ?>
            <tr><td class="text-start"><?= e($s['name']) ?></td><td><?= (float)$s['total_marks'] ?></td><td><?= (float)$s['pass_marks'] ?></td>
                <td style="<?= $fail ? 'color:#b91c1c;font-weight:700' : '' ?>"><?= $s['is_absent'] ? 'Absent' : ($s['obtained_marks'] === null ? '-' : $o) ?></td><td><?= round($pct, 1) ?></td><td><?= e($g['name'] ?? '') ?></td><td><?= e($s['remarks'] ?: ($fail ? t('Fail') : '')) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th class="text-start"><?= e(t('Grand Total')) ?></th><th><?= (float)$r['total_marks'] ?></th><th></th><th><?= (float)$r['obtained_marks'] ?></th><th><?= e($r['percentage']) ?>%</th><th><?= e($r['grade']) ?></th><th></th></tr></tfoot>
    </table>
    <div class="d-flex flex-wrap gap-4 my-2" style="font-size:13px">
        <div><?= e(t('Result')) ?>: <b style="color:<?= $r['result_status'] === 'pass' ? '#15803d' : '#b91c1c' ?>"><?= e(strtoupper($r['result_status'])) ?></b></div>
        <div><?= e(t('Position')) ?>: <b><?= e(ResultService::ordinal($r['position'] ? (int)$r['position'] : null)) ?></b> <?= e(t('of')) ?> <?= (int)$c['strength'] ?></div>
        <div><?= e(t('Attendance')) ?>: <b><?= $attP ?>/<?= $attT ?></b> (<?= $attT ? round($attP / $attT * 100) : 0 ?>%)</div>
        <div><?= e(t('Remarks')) ?>: <b><?= e($r['remarks']) ?></b></div>
    </div>
    <div class="small text-muted"><?= e(t('Grading')) ?>: <?php foreach (DB::all('SELECT name, min_percent, max_percent FROM grades ORDER BY min_percent DESC') as $g): ?><?= e($g['name']) ?> (<?= (float)$g['min_percent'] ?>-<?= (float)$g['max_percent'] ?>) &nbsp;<?php endforeach; ?></div>
    <div class="sig-row" style="margin-top:40px"><div><?= e(t('Class Teacher')) ?></div><div><?= e(t('Parent / Guardian')) ?></div><div><?= e(setting('principal_name') ?: t('Principal')) ?></div></div>
</div>
<?php endforeach; ?>
