<?php
return [
    'table' => 'message_logs', 'title' => 'SMS & Email Log', 'singular' => 'Message', 'icon' => 'bi-chat-left-text', 'group' => 'Communication',
    'perm' => ['view' => 'notices.manage', 'manage' => 'notices.manage'], 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
    'order' => 't.id DESC', 'search' => ['t.recipient', 't.subject', 't.message'],
    'filters' => [
        'ch' => ['label' => 'Channel', 'sql' => 't.channel', 'options' => ['sms' => 'SMS', 'email' => 'Email']],
        'st' => ['label' => 'Status', 'sql' => 't.status', 'options' => ['sent' => 'Sent', 'failed' => 'Failed']],
    ],
    'columns' => [
        'created_at' => ['Date', 'render' => fn($r) => e(fmt_date($r['created_at'], 'd-M-Y h:i A'))], 'channel' => ['Channel', 'render' => fn($r) => strtoupper(e($r['channel']))],
        'recipient' => ['Recipient'], 'message' => ['Message'], 'status' => ['Status', 'render' => fn($r) => '<span class="badge text-bg-' . ($r['status'] === 'sent' ? 'success' : 'danger') . ' badge-soft">' . e(t(ucfirst($r['status']))) . '</span>'],
    ],
    'fields' => [],
];
