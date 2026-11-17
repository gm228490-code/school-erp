<?php partial('page_head', ['title' => t('Generate Monthly Fees'), 'icon' => 'bi-lightning-charge', 'crumbs' => [t('Fees') => null, t('Generate') => null]]);
$classes = DB::all("SELECT c.id, c.name, (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id AND s.status='active') AS n FROM classes c ORDER BY c.numeric_order");
$extra = DB::pairs("SELECT id, name FROM fee_categories WHERE frequency<>'monthly' ORDER BY id");
$dueDay = min(28, max(1, (int)setting('fee_due_day', '10'))); ?>
<div class="row g-4">
    <div class="col-lg-7">
        <form method="post" action="<?= e(url('fees/do_generate')) ?>" class="card" data-confirm="<?= e(t('Generate invoices now? Existing invoices for the month are skipped automatically.')) ?>">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label"><?= e(t('Fee Month')) ?> *</label><input type="month" name="month" class="form-control" value="<?= date('Y-m') ?>" required></div>
                    <div class="col-md-6"><label class="form-label"><?= e(t('Due Date')) ?> *</label><input type="date" name="due_date" class="form-control" value="<?= date('Y-m-') . str_pad((string)$dueDay, 2, '0', STR_PAD_LEFT) ?>" required></div>
                    <div class="col-12"><label class="form-label d-flex justify-content-between"><span><?= e(t('Classes')) ?> *</span><span><input type="checkbox" class="form-check-input" data-check-all=".chk-cls" checked> <?= e(t('All')) ?></span></label>
                        <div class="row g-1"><?php foreach ($classes as $c): ?><div class="col-6 col-md-4"><label class="form-check"><input class="form-check-input chk-cls" type="checkbox" name="classes[]" value="<?= (int)$c['id'] ?>" checked> <?= e($c['name']) ?> <span class="text-muted small">(<?= (int)$c['n'] ?>)</span></label></div><?php endforeach; ?></div></div>
                    <div class="col-12"><label class="form-label"><?= e(t('Also include (optional)')) ?></label>
                        <div class="row g-1"><?php foreach ($extra as $id => $n): ?><div class="col-6 col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="extra[]" value="<?= (int)$id ?>"> <?= e($n) ?></label></div><?php endforeach; ?></div>
                        <div class="form-text"><?= e(t('Monthly categories, transport fee, individual rates and active discounts are applied automatically.')) ?></div></div>
                    <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="notify" value="1" checked> <?= e(t('Notify parents in the portal')) ?></label></div>
                </div>
            </div>
            <div class="card-body border-top"><button class="btn btn-primary"><i class="bi bi-lightning-charge"></i> <?= e(t('Generate Invoices')) ?></button></div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><?= e(t('Recent Months')) ?></div>
            <table class="table table-sm mb-0"><thead><tr><th><?= e(t('Month')) ?></th><th><?= e(t('Invoices')) ?></th><th class="text-end"><?= e(t('Billed')) ?></th><th class="text-end"><?= e(t('Collected')) ?></th></tr></thead><tbody>
            <?php foreach ($recent as $r): ?><tr><td><a href="<?= e(url('fees', ['month' => $r['fee_month']])) ?>"><?= e(fmt_month($r['fee_month'])) ?></a></td><td><?= (int)$r['n'] ?></td><td class="text-end"><?= e(money($r['net'])) ?></td><td class="text-end"><?= e(money($r['paid'])) ?><div class="progress progress-thin"><div class="progress-bar bg-success" style="width:<?= $r['net'] > 0 ? min(100, round($r['paid'] / $r['net'] * 100)) : 0 ?>%"></div></div></td></tr><?php endforeach; ?>
            <?php if (!$recent): ?><tr><td colspan="4" class="text-muted"><?= e(t('Nothing generated yet.')) ?></td></tr><?php endif; ?>
            </tbody></table></div>
    </div>
</div>
