<?php
declare(strict_types=1);

/**
 * Render a Bootstrap form control.
 * $f: label, type, options (array|callable), required, col, help, placeholder, attrs, accept, rows, step, readonly
 */
function form_field(string $name, array $f, mixed $value = null, array $errors = []): string
{
    $type = $f['type'] ?? 'text';
    $col = $f['col'] ?? 6;
    if ($type === 'heading') {
        return '<div class="col-12"><h6 class="form-section-title">' . e(t($f['label'])) . '</h6></div>';
    }
    $id = 'f_' . $name . '_' . substr(md5((string)mt_rand()), 0, 5);
    $required = !empty($f['required']) || str_contains((string)($f['rules'] ?? ''), 'required');
    $err = $errors[$name] ?? null;
    $cls = 'form-control' . ($err ? ' is-invalid' : '');
    $attrs = ($required && !in_array($type, ['file', 'image', 'checkbox'], true) ? ' required' : '') . (!empty($f['readonly']) ? ' readonly' : '') . ' ' . ($f['attrs'] ?? '');
    $ph = isset($f['placeholder']) ? ' placeholder="' . e(t($f['placeholder'])) . '"' : '';
    $label = '<label class="form-label" for="' . $id . '">' . e(t($f['label'])) . ($required ? ' <span class="text-danger">*</span>' : '') . '</label>';
    $value = $value ?? ($f['default'] ?? '');
    $max = '';
    if (preg_match('/max:(\d+)/', (string)($f['rules'] ?? ''), $m) && !in_array($type, ['number', 'decimal'], true)) {
        $max = ' maxlength="' . $m[1] . '"';
    }

    switch ($type) {
        case 'textarea':
            $ctl = '<textarea class="' . $cls . '" id="' . $id . '" name="' . e($name) . '" rows="' . ($f['rows'] ?? 3) . '"' . $attrs . $ph . $max . '>' . e($value) . '</textarea>';
            break;
        case 'select':
            $opts = $f['options'] ?? [];
            $opts = is_callable($opts) ? $opts() : $opts;
            $translated = [];
            foreach ($opts as $k => $v) {
                $translated[$k] = t((string)$v);
            }
            $ctl = '<select class="form-select' . ($err ? ' is-invalid' : '') . '" id="' . $id . '" name="' . e($name) . '"' . $attrs . '>' . options($translated, $value, $f['placeholder'] ?? t('-- Select --')) . '</select>';
            break;
        case 'checkbox':
            return '<div class="col-md-' . $col . ' d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="' . $id . '" name="' . e($name) . '" value="1"' . ($value ? ' checked' : '') . $attrs . '><label class="form-check-label" for="' . $id . '">' . e(t($f['label'])) . '</label></div></div>';
        case 'image':
        case 'file':
            $accept = $type === 'image' ? 'image/jpeg,image/png,image/webp' : ($f['accept'] ?? '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt');
            $prev = '';
            if ($value) {
                $prev = $type === 'image'
                    ? '<img src="' . e(upload_url($value)) . '" class="img-thumbnail mt-2" style="max-height:80px" alt="">'
                    : '<div class="small mt-1"><a href="' . e(upload_url($value)) . '" target="_blank"><i class="bi bi-paperclip"></i> ' . t('Current file') . '</a></div>';
            }
            $ctl = '<input type="file" class="' . $cls . '" id="' . $id . '" name="' . e($name) . '" accept="' . e($accept) . '"' . ($type === 'image' ? ' data-preview' : '') . $attrs . '>' . $prev;
            break;
        default:
            $htmlType = match ($type) {
                'decimal' => 'number', 'month' => 'month', default => $type,
            };
            $step = $type === 'decimal' ? ' step="' . ($f['step'] ?? '0.01') . '"' : ($type === 'number' ? ' step="' . ($f['step'] ?? '1') . '"' : '');
            $min = isset($f['min']) ? ' min="' . e($f['min']) . '"' : (in_array($type, ['number', 'decimal'], true) ? ' min="0"' : '');
            $ctl = '<input type="' . e($htmlType) . '" class="' . $cls . '" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '"' . $step . $min . $attrs . $ph . $max . '>';
    }
    $help = !empty($f['help']) ? '<div class="form-text">' . e(t($f['help'])) . '</div>' : '';
    $feedback = '<div class="invalid-feedback" data-error-for="' . e($name) . '">' . e($err ? t($err) : '') . '</div>';
    return '<div class="col-md-' . $col . '">' . $label . $ctl . $feedback . $help . '</div>';
}

function form_errors(): array
{
    $e = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    return $e;
}
