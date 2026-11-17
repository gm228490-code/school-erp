<?php
declare(strict_types=1);

/**
 * Definition-driven CRUD engine.
 * A resource file in app/resources/{name}.php returns an array describing the
 * table, list columns, search, filters, form fields, validation, hooks and permissions.
 */
final class Crud
{
    public array $d;
    public string $res;

    private function __construct(string $res, array $d)
    {
        $this->res = $res;
        $this->d = $d + [
            'from' => null, 'select' => 't.*', 'order' => 't.id DESC', 'search' => [], 'filters' => [],
            'columns' => [], 'fields' => [], 'per_page' => 20, 'icon' => 'bi-table', 'stamp' => [],
            'can_create' => true, 'can_edit' => true, 'can_delete' => true, 'modal_size' => 'modal-lg',
        ];
        $this->d['from'] ??= '`' . $d['table'] . '` t';
        $this->d['singular'] ??= rtrim($d['title'], 's');
    }

    public static function load(string $res): self
    {
        if (!preg_match('/^[a-z_]+$/', $res) || !is_file($f = APP_PATH . '/resources/' . $res . '.php')) {
            abort(404);
        }
        return new self($res, require $f);
    }

    public function perm(string $action): string
    {
        $p = $this->d['perm'];
        if (is_array($p)) {
            return $p[$action] ?? $p['manage'] ?? $p['view'];
        }
        return $action === 'view' ? "$p.view" : "$p.manage";
    }

    public function allowed(string $action): bool
    {
        if ($action !== 'view' && empty($this->d['can_' . $action])) {
            return false;
        }
        return Auth::can($this->perm($action));
    }

    /** WHERE clause and params from scope + search + filters */
    public function where(): array
    {
        $w = ['1=1'];
        $p = [];
        if (isset($this->d['scope'])) {
            [$sql, $sp] = ($this->d['scope'])();
            if ($sql) {
                $w[] = $sql;
                $p = array_merge($p, $sp);
            }
        }
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q !== '' && $this->d['search']) {
            $w[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $this->d['search'])) . ')';
            foreach ($this->d['search'] as $_) {
                $p[] = '%' . $q . '%';
            }
        }
        foreach ($this->d['filters'] as $key => $f) {
            $v = $_GET['f_' . $key] ?? '';
            if ($v === '' || $v === null) {
                continue;
            }
            if (($f['type'] ?? 'select') === 'date_from') {
                if (valid_date($v)) { $w[] = $f['sql'] . ' >= ?'; $p[] = $v; }
            } elseif (($f['type'] ?? 'select') === 'date_to') {
                if (valid_date($v)) { $w[] = $f['sql'] . ' <= ?'; $p[] = $v; }
            } else {
                $w[] = $f['sql'] . ' = ?';
                $p[] = $v;
            }
        }
        return [implode(' AND ', $w), $p];
    }

    public function orderBy(): string
    {
        $allowed = [];
        foreach ($this->d['columns'] as $k => $c) {
            if (($c['sort'] ?? true) !== false) {
                $allowed[$k] = is_string($c['sort'] ?? null) ? $c['sort'] : (str_contains($k, '.') ? $k : (isset($this->d['fields'][$k]) ? "t.`$k`" : "`$k`"));
            }
        }
        return order_by($allowed, $this->d['order']);
    }

    public function listData(): array
    {
        [$where, $params] = $this->where();
        $from = $this->d['from'];
        return paginate("SELECT COUNT(*) FROM $from WHERE $where", "SELECT {$this->d['select']} FROM $from WHERE $where ORDER BY " . $this->orderBy(), $params, (int)$this->d['per_page']);
    }

    public function allRows(): array
    {
        [$where, $params] = $this->where();
        return DB::all("SELECT {$this->d['select']} FROM {$this->d['from']} WHERE $where ORDER BY " . $this->orderBy() . ' LIMIT 20000', $params);
    }

    public function find(int $id): ?array
    {
        [$where, $params] = $this->where();
        return DB::row("SELECT {$this->d['select']} FROM {$this->d['from']} WHERE t.id=? AND $where", array_merge([$id], $params));
    }

    /** Render one cell */
    public function cell(string $key, array $row, bool $plain = false): string
    {
        $c = $this->d['columns'][$key];
        if (isset($c['render'])) {
            $h = (string)($c['render'])($row);
            return $plain ? trim(html_entity_decode(strip_tags($h))) : $h;
        }
        $v = $row[$c['field'] ?? $key] ?? '';
        $type = $c['type'] ?? 'text';
        if ($plain) {
            return match ($type) {
                'money' => number_format((float)$v, 2, '.', ''),
                'date' => fmt_date($v),
                'bool' => $v ? 'Yes' : 'No',
                'image' => '',
                default => (string)$v,
            };
        }
        return match ($type) {
            'money' => '<span class="text-nowrap">' . e(money($v)) . '</span>',
            'date' => e(fmt_date($v)),
            'badge' => status_badge((string)$v),
            'bool' => $v ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash-circle text-muted"></i>',
            'image' => $v ? '<img src="' . e(upload_url($v)) . '" class="avatar-sm" alt="">' : '<span class="avatar-sm avatar-ph"><i class="bi bi-image"></i></span>',
            'file' => $v ? '<a href="' . e(upload_url($v)) . '" target="_blank"><i class="bi bi-paperclip"></i> ' . t('View') . '</a>' : '',
            default => e(mb_strimwidth((string)$v, 0, 80, '…')),
        };
    }

    public function fieldOptions(array $f): array
    {
        $o = $f['options'] ?? [];
        return is_callable($o) ? $o() : $o;
    }

    /** Collect & validate POST data according to field definitions */
    public function collect(?int $id): array
    {
        $data = [];
        $rules = [];
        $labels = [];
        foreach ($this->d['fields'] as $name => $f) {
            $type = $f['type'] ?? 'text';
            if (in_array($type, ['file', 'image', 'heading'], true) || !empty($f['readonly'])) {
                continue;
            }
            $labels[$name] = $f['label'];
            $r = $f['rules'] ?? '';
            if (!empty($f['unique'])) {
                $r .= ($r ? '|' : '') . 'unique:' . $this->d['table'] . ',' . $name . ($id ? ',' . $id : '');
            }
            if ($type === 'checkbox') {
                $data[$name] = isset($_POST[$name]) ? 1 : 0;
                continue;
            }
            $v = $_POST[$name] ?? null;
            $data[$name] = is_string($v) ? trim($v) : $v;
            if (in_array($type, ['select', 'radio'], true) && $data[$name] !== null && $data[$name] !== '') {
                $opts = $this->fieldOptions($f);
                if (!array_key_exists($data[$name], $opts)) {
                    throw new ValidationException([$name => $f['label'] . ' is invalid.']);
                }
            }
            if ($type === 'date') { $r .= '|date'; }
            if ($type === 'email') { $r .= '|email'; }
            if ($type === 'time') { $r .= '|time'; }
            if (in_array($type, ['number', 'decimal'], true)) { $r .= '|numeric'; }
            $rules[$name] = trim($r, '|');
        }
        validate_or_fail($data, $rules, $labels);
        $data = nullify($data);
        // file uploads
        foreach ($this->d['fields'] as $name => $f) {
            $type = $f['type'] ?? 'text';
            if (in_array($type, ['file', 'image'], true)) {
                $path = upload_file($name, $f['dir'] ?? $this->res, $type === 'image' ? 'image' : 'doc', !$id && !empty($f['required']));
                if ($path) {
                    $data[$name] = $path;
                }
            }
        }
        return $data;
    }

    public function save(?int $id): int
    {
        $data = $this->collect($id);
        $existing = $id ? $this->find($id) : null;
        if ($id && !$existing) {
            abort(404);
        }
        if (isset($this->d['before_save'])) {
            $data = ($this->d['before_save'])($data, $id, $existing);
        }
        return DB::tx(function () use ($data, $id, $existing) {
            if ($id) {
                foreach ($this->d['fields'] as $name => $f) {
                    if (in_array($f['type'] ?? '', ['file', 'image'], true) && isset($data[$name]) && !empty($existing[$name])) {
                        delete_upload($existing[$name]);
                    }
                }
                if ($data) {
                    DB::update($this->d['table'], $data, 'id=?', [$id]);
                }
                audit('update', $this->res, (string)$id, json_encode(array_diff_key($data, ['password' => 1]), JSON_UNESCAPED_UNICODE));
            } else {
                foreach ($this->d['stamp'] as $col) {
                    $data[$col] = Auth::id();
                }
                $id = DB::insert($this->d['table'], $data);
                audit('create', $this->res, (string)$id, json_encode(array_diff_key($data, ['password' => 1]), JSON_UNESCAPED_UNICODE));
            }
            if (isset($this->d['after_save'])) {
                ($this->d['after_save'])($id, $data, $existing === null);
            }
            return $id;
        });
    }

    public function delete(int $id): void
    {
        $row = $this->find($id);
        if (!$row) {
            abort(404);
        }
        if (isset($this->d['before_delete']) && ($err = ($this->d['before_delete'])($id, $row))) {
            throw new HttpException($err, 400);
        }
        DB::tx(function () use ($id, $row) {
            DB::delete($this->d['table'], 'id=?', [$id]);
            if (isset($this->d['after_delete'])) {
                ($this->d['after_delete'])($id, $row);
            }
        });
        foreach ($this->d['fields'] as $name => $f) {
            if (in_array($f['type'] ?? '', ['file', 'image'], true)) {
                delete_upload($row[$name] ?? null);
            }
        }
        audit('delete', $this->res, (string)$id, json_encode(array_intersect_key($row, array_flip(array_slice(array_keys($row), 0, 6))), JSON_UNESCAPED_UNICODE));
    }
}
