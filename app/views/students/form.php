<?php
/** @var ?array $s @var ?array $par @var array $errors */
$isEdit = (bool)$s;
$v = function (string $k, ?array $src = null, ?string $srcKey = null) use ($s, $par) {
    $o = old($k, null);
    if ($o !== null) return $o;
    $src ??= $s;
    return $src[$srcKey ?? $k] ?? '';
};
$pv = fn($k, $srcKey = null) => $v($k, $par, $srcKey ?? $k);
$classId = (int)$v('class_id');
$f = fn($name, $def, $val) => form_field($name, $def, $val, $errors);
partial('page_head', ['title' => $isEdit ? t('Edit Student') . ': ' . student_name($s) : t('New Student Admission'), 'icon' => $isEdit ? 'bi-pencil-square' : 'bi-person-plus',
    'crumbs' => [t('Students') => url('students'), ($isEdit ? t('Edit') : t('New Admission')) => null]]);
?>
<form method="post" action="<?= e(url($isEdit ? 'students/update' : 'students/store', $isEdit ? ['id' => $s['id']] : [])) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-person me-1"></i><?= e(t('Student Information')) ?></div>
                <div class="card-body"><div class="row g-3">
                    <?php if ($isEdit): ?><div class="col-md-4"><label class="form-label"><?= e(t('Registration No')) ?></label><input class="form-control" value="<?= e($s['reg_no']) ?>" disabled></div><?php else: ?>
                    <div class="col-12"><div class="alert alert-light border small py-2 mb-0"><i class="bi bi-magic me-1"></i><?= e(t('Registration number is generated automatically on save.')) ?></div></div><?php endif; ?>
                    <?= $f('first_name', ['label' => 'First Name', 'rules' => 'required|max:60', 'col' => 4], $v('first_name')) ?>
                    <?= $f('last_name', ['label' => 'Last Name', 'rules' => 'max:60', 'col' => 4], $v('last_name')) ?>
                    <?= $f('gender', ['label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], 'rules' => 'required', 'col' => 4], $v('gender')) ?>
                    <?= $f('dob', ['label' => 'Date of Birth', 'type' => 'date', 'col' => 4], $v('dob')) ?>
                    <?= $f('b_form', ['label' => 'B-Form / CNIC No.', 'placeholder' => '00000-0000000-0', 'col' => 4], $v('b_form')) ?>
                    <?= $f('blood_group', ['label' => 'Blood Group', 'type' => 'select', 'options' => array_combine($bg = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], $bg), 'col' => 4], $v('blood_group')) ?>
                    <?= $f('religion', ['label' => 'Religion', 'type' => 'select', 'options' => ['Islam' => 'Islam', 'Christianity' => 'Christianity', 'Hinduism' => 'Hinduism', 'Sikhism' => 'Sikhism', 'Other' => 'Other'], 'col' => 4], $v('religion') ?: 'Islam') ?>
                    <?= $f('nationality', ['label' => 'Nationality', 'col' => 4], $v('nationality') ?: 'Pakistani') ?>
                    <?= $f('phone', ['label' => 'Contact Number', 'type' => 'tel', 'col' => 4], $v('phone')) ?>
                    <?= $f('email', ['label' => 'Email', 'type' => 'email', 'col' => 6], $v('email')) ?>
                    <?= $f('city', ['label' => 'City', 'col' => 6], $v('city')) ?>
                    <?= $f('address', ['label' => 'Complete Address', 'type' => 'textarea', 'rows' => 2, 'col' => 12], $v('address')) ?>
                </div></div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-people me-1"></i><?= e(t('Parent / Guardian Information')) ?></span>
                    <span class="small text-muted"><?= e(t('Same CNIC links siblings to one parent record')) ?></span></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-12">
                        <label class="form-label"><?= e(t('Find existing parent (sibling admission)')) ?></label>
                        <div class="position-relative"><input type="text" class="form-control" id="parentSearch" placeholder="<?= e(t('Search by father name, CNIC or phone…')) ?>" autocomplete="off">
                            <div class="list-group position-absolute w-100 shadow-sm" id="parentResults" style="z-index:20"></div></div>
                        <input type="hidden" name="parent_id" id="parent_id" value="<?= e(old('parent_id', $s['parent_id'] ?? '')) ?>">
                        <div class="form-text" id="parentPicked"><?= $par ? e(t('Linked parent')) . ': <b>' . e($par['father_name']) . '</b>' : '' ?></div>
                    </div>
                    <?= $f('h_f', ['label' => 'Father', 'type' => 'heading'], null) ?>
                    <?= $f('father_name', ['label' => "Father's Name", 'col' => 6, 'rules' => $par ? '' : 'required'], $pv('father_name')) ?>
                    <?= $f('father_cnic', ['label' => "Father's CNIC", 'placeholder' => '00000-0000000-0', 'col' => 6], $pv('father_cnic')) ?>
                    <?= $f('father_phone', ['label' => "Father's Phone", 'type' => 'tel', 'col' => 6], $pv('father_phone')) ?>
                    <?= $f('father_occupation', ['label' => "Father's Occupation", 'col' => 6], $pv('father_occupation')) ?>
                    <?= $f('h_m', ['label' => 'Mother', 'type' => 'heading'], null) ?>
                    <?= $f('mother_name', ['label' => "Mother's Name", 'col' => 6], $pv('mother_name')) ?>
                    <?= $f('mother_cnic', ['label' => "Mother's CNIC", 'col' => 6], $pv('mother_cnic')) ?>
                    <?= $f('mother_phone', ['label' => "Mother's Phone", 'type' => 'tel', 'col' => 6], $pv('mother_phone')) ?>
                    <?= $f('mother_occupation', ['label' => "Mother's Occupation", 'col' => 6], $pv('mother_occupation')) ?>
                    <?= $f('h_g', ['label' => 'Guardian (if other than parents)', 'type' => 'heading'], null) ?>
                    <?= $f('guardian_name', ['label' => 'Guardian Name', 'col' => 6], $pv('guardian_name')) ?>
                    <?= $f('guardian_relation', ['label' => 'Relation', 'col' => 6], $pv('guardian_relation')) ?>
                    <?= $f('guardian_phone', ['label' => 'Guardian Phone', 'type' => 'tel', 'col' => 6], $pv('guardian_phone')) ?>
                    <?= $f('guardian_cnic', ['label' => 'Guardian CNIC', 'col' => 6], $pv('guardian_cnic')) ?>
                    <?= $f('parent_email', ['label' => 'Parent Email', 'type' => 'email', 'col' => 6], $v('parent_email', $par, 'email')) ?>
                    <?= $f('parent_address', ['label' => 'Parent Address (if different)', 'col' => 6], $v('parent_address', $par, 'address')) ?>
                </div></div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-building me-1"></i><?= e(t('Previous School')) ?></div>
                <div class="card-body"><div class="row g-3">
                    <?= $f('previous_school', ['label' => 'Previous School Name', 'col' => 8], $v('previous_school')) ?>
                    <?= $f('previous_class', ['label' => 'Last Class Attended', 'col' => 4], $v('previous_class')) ?>
                </div></div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-mortarboard me-1"></i><?= e(t('Admission Details')) ?></div>
                <div class="card-body"><div class="row g-3">
                    <?= $f('admission_date', ['label' => 'Admission Date', 'type' => 'date', 'rules' => 'required', 'col' => 12], $v('admission_date') ?: date('Y-m-d')) ?>
                    <?= $f('class_id', ['label' => 'Class', 'type' => 'select', 'options' => DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), 'rules' => 'required', 'col' => 12, 'attrs' => 'id="admClass"'], $classId ?: '') ?>
                    <?= $f('section_id', ['label' => 'Section', 'type' => 'select', 'options' => $classId ? DB::pairs('SELECT id,name FROM sections WHERE class_id=? ORDER BY name', [$classId]) : [], 'col' => 6, 'attrs' => 'data-sections-for="#admClass"'], $v('section_id')) ?>
                    <?= $f('roll_no', ['label' => 'Roll No', 'col' => 6, 'placeholder' => 'Auto'], $v('roll_no')) ?>
                    <?= $f('route_id', ['label' => 'Transport Route', 'type' => 'select', 'options' => DB::pairs("SELECT id, CONCAT(name,' (',monthly_fee,')') FROM transport_routes ORDER BY name"), 'placeholder' => 'No transport', 'col' => 12], $v('route_id')) ?>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="override_capacity" value="1" id="oc"><label class="form-check-label small" for="oc"><?= e(t('Allow over capacity')) ?></label></div></div>
                </div></div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-camera me-1"></i><?= e(t('Photo & Documents')) ?></div>
                <div class="card-body"><div class="row g-3">
                    <?= $f('photo', ['label' => 'Student Photo', 'type' => 'image', 'col' => 12], $s['photo'] ?? null) ?>
                    <div class="col-12">
                        <label class="form-label"><?= e(t('Documents (B-Form, previous result, certificates)')) ?></label>
                        <div id="docRows">
                            <div class="input-group input-group-sm mb-2"><input type="text" name="doc_title[]" class="form-control" placeholder="<?= e(t('Title')) ?>"><input type="file" name="doc_file[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-link px-0" onclick="const r=document.querySelector('#docRows .input-group').cloneNode(true);r.querySelectorAll('input').forEach(i=>i.value='');document.getElementById('docRows').appendChild(r)"><i class="bi bi-plus"></i> <?= e(t('Add another document')) ?></button>
                    </div>
                </div></div>
            </div>
            <?php if (!$isEdit): ?>
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-gear me-1"></i><?= e(t('Options')) ?></div>
                <div class="card-body">
                    <?php if (can('fees.manage')): ?>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="admission_invoice" value="1" id="ai" checked><label class="form-check-label" for="ai"><?= e(t('Generate admission fee invoice')) ?></label></div>
                    <div class="form-check mb-2 ms-3"><input class="form-check-input" type="checkbox" name="first_month" value="1" id="fm"><label class="form-check-label small" for="fm"><?= e(t("Include this month's monthly fees")) ?></label></div>
                    <?php endif; ?>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="create_parent_login" value="1" id="pl"><label class="form-check-label" for="pl"><?= e(t('Create parent portal login')) ?></label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="create_student_login" value="1" id="sl"><label class="form-check-label" for="sl"><?= e(t('Create student portal login')) ?></label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="allow_duplicate" value="1" id="ad"><label class="form-check-label small text-muted" for="ad"><?= e(t('Allow possible duplicate (same name, father & DOB)')) ?></label></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="d-grid gap-2">
                <button class="btn btn-primary btn-lg"><i class="bi bi-check2-circle"></i> <?= e($isEdit ? t('Update Student') : t('Admit Student')) ?></button>
                <a href="<?= e($isEdit ? url('students/show', ['id' => $s['id']]) : url('students')) ?>" class="btn btn-light"><?= e(t('Cancel')) ?></a>
            </div>
        </div>
    </div>
</form>
<?php ob_start(); ?>
<script>
(function () {
  const inp = document.getElementById('parentSearch'), list = document.getElementById('parentResults');
  let tm;
  inp.addEventListener('input', () => {
    clearTimeout(tm);
    tm = setTimeout(async () => {
      list.innerHTML = '';
      if (inp.value.trim().length < 2) return;
      const r = await api(APP.base + '/index.php?r=ajax/parents&q=' + encodeURIComponent(inp.value.trim()));
      (r.items || []).forEach(p => {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'list-group-item list-group-item-action small';
        b.textContent = `${p.father_name} — ${p.father_cnic || ''} ${p.father_phone || ''}`;
        b.onclick = () => {
          document.getElementById('parent_id').value = p.id;
          document.getElementById('parentPicked').innerHTML = '';
          const bEl = document.createElement('b'); bEl.textContent = p.father_name;
          document.getElementById('parentPicked').append('Linked parent: ', bEl, ' (existing details kept; fill fields below only to update them)');
          ['father_name', 'father_cnic', 'father_phone', 'mother_name'].forEach(k => { const f = document.querySelector(`[name=${k}]`); if (f && p[k]) f.value = p[k]; });
          list.innerHTML = ''; inp.value = '';
        };
        list.appendChild(b);
      });
    }, 250);
  });
})();
</script>
<?php $scripts = ob_get_clean(); ?>
