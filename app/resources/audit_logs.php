<?php
return [
    'table' => 'audit_logs', 'title' => 'Audit Logs', 'singular' => 'Log', 'icon' => 'bi-activity', 'group' => 'Administration',
    'perm' => ['view' => 'system.audit', 'manage' => 'system.audit'], 'can_create' => false, 'can_edit' => false, 'can_delete' => false, 'per_page' => 50,
    'from' => 'audit_logs t LEFT JOIN users u ON u.id=t.user_id', 'select' => 't.*, u.name AS user_name, u.username',
    'order' => 't.id DESC', 'search' => ['t.details', 't.action', 't.module', 'u.name', 't.ip', 't.record_id'],
    'filters' => [
        'module' => ['label' => 'Module', 'sql' => 't.module', 'options' => fn() => DB::pairs('SELECT DISTINCT module, module FROM audit_logs ORDER BY module')],
        'action' => ['label' => 'Action', 'sql' => 't.action', 'options' => fn() => DB::pairs('SELECT DISTINCT action, action FROM audit_logs ORDER BY action')],
        'user' => ['label' => 'User', 'sql' => 't.user_id', 'options' => fn() => DB::pairs('SELECT id, name FROM users ORDER BY name')],
        'from' => ['label' => 'From', 'type' => 'date_from', 'sql' => 'DATE(t.created_at)'],
        'to' => ['label' => 'To', 'type' => 'date_to', 'sql' => 'DATE(t.created_at)'],
    ],
    'columns' => [
        'created_at' => ['Date & Time', 'render' => fn($r) => '<span class="text-nowrap">' . e(fmt_date($r['created_at'], 'd-M-Y h:i:s A')) . '</span>'],
        'user_name' => ['User', 'sort' => 'u.name', 'render' => fn($r) => e($r['user_name'] ?: t('Guest / System'))],
        'action' => ['Action', 'render' => fn($r) => '<span class="badge text-bg-' . (str_contains($r['action'], 'fail') || $r['action'] === 'delete' ? 'danger' : ($r['action'] === 'create' ? 'success' : 'primary')) . ' badge-soft">' . e($r['action']) . '</span>'],
        'module' => ['Module'], 'record_id' => ['Record'],
        'details' => ['Details', 'render' => fn($r) => '<span class="small text-muted d-inline-block text-truncate" style="max-width:340px" title="' . e($r['details']) . '">' . e($r['details']) . '</span>'],
        'ip' => ['IP Address'],
    ],
    'fields' => [],
];
