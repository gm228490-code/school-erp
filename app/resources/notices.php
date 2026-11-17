<?php
$types = ['general' => 'General', 'exam' => 'Examination', 'holiday' => 'Holiday', 'fee' => 'Fee', 'event' => 'Event'];
$aud = ['all' => 'Everyone', 'students' => 'Students', 'parents' => 'Parents', 'teachers' => 'Teachers', 'staff' => 'Staff', 'class' => 'Specific Class'];
return [
    'table' => 'notices', 'title' => 'Announcements', 'singular' => 'Announcement', 'icon' => 'bi-megaphone', 'group' => 'Communication',
    'perm' => ['view' => 'notices.view', 'manage' => 'notices.manage'], 'stamp' => ['created_by'],
    'from' => 'notices t LEFT JOIN classes c ON c.id=t.class_id LEFT JOIN users u ON u.id=t.created_by', 'select' => 't.*, c.name AS class_name, u.name AS author',
    'order' => 't.publish_date DESC, t.id DESC', 'search' => ['t.title', 't.body'],
    'filters' => [
        'type' => ['label' => 'Type', 'sql' => 't.notice_type', 'options' => $types],
        'aud' => ['label' => 'Audience', 'sql' => 't.audience', 'options' => $aud],
    ],
    'columns' => [
        'title' => ['Title', 'render' => fn($r) => '<div class="fw-semibold">' . e($r['title']) . '</div><div class="small text-muted text-truncate" style="max-width:380px">' . e($r['body']) . '</div>'],
        'notice_type' => ['Type', 'render' => fn($r) => '<span class="badge text-bg-primary badge-soft">' . e(t($types[$r['notice_type']])) . '</span>'],
        'audience' => ['Audience', 'render' => fn($r) => e(t($aud[$r['audience']])) . ($r['class_name'] ? ': ' . e($r['class_name']) : '')],
        'publish_date' => ['Published', 'type' => 'date'], 'expiry_date' => ['Expires', 'type' => 'date'], 'author' => ['By', 'sort' => 'u.name'],
    ],
    'fields' => [
        'title' => ['label' => 'Title', 'rules' => 'required|max:200', 'col' => 12],
        'body' => ['label' => 'Message', 'type' => 'textarea', 'rules' => 'required|max:5000', 'col' => 12, 'rows' => 5],
        'notice_type' => ['label' => 'Type', 'type' => 'select', 'options' => $types, 'rules' => 'required', 'default' => 'general', 'col' => 4],
        'audience' => ['label' => 'Audience', 'type' => 'select', 'options' => $aud, 'rules' => 'required', 'default' => 'all', 'col' => 4],
        'class_id' => ['label' => 'Class (if specific class)', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM classes ORDER BY numeric_order'), 'col' => 4],
        'publish_date' => ['label' => 'Publish Date', 'type' => 'date', 'rules' => 'required', 'default' => date('Y-m-d')],
        'expiry_date' => ['label' => 'Expiry Date', 'type' => 'date'],
        'send_notification' => ['label' => 'Send in-app notification to the audience', 'type' => 'checkbox', 'default' => 1, 'create_only' => true],
        'send_sms' => ['label' => 'Also send SMS / Email to parents (if configured)', 'type' => 'checkbox', 'create_only' => true],
    ],
    'before_save' => function ($d) {
        if ($d['audience'] === 'class' && empty($d['class_id'])) {
            throw new ValidationException(['class_id' => 'Select the class for this announcement.']);
        }
        if ($d['audience'] !== 'class') {
            $d['class_id'] = null;
        }
        $GLOBALS['_notice_flags'] = ['notify' => !empty($d['send_notification']), 'sms' => !empty($d['send_sms'])];
        unset($d['send_notification'], $d['send_sms']);
        return $d;
    },
    'after_save' => function ($id, $d, $isNew) {
        $flags = $GLOBALS['_notice_flags'] ?? [];
        if (!$isNew || (empty($flags['notify']) && empty($flags['sms']))) {
            return;
        }
        $users = NoticeService::audienceUserIds($d['audience'], $d['class_id'] ? (int)$d['class_id'] : null);
        if (!empty($flags['notify'])) {
            notify($users, $d['title'], mb_strimwidth($d['body'], 0, 300, '…'), 'index.php?r=' . (in_array($d['audience'], ['students', 'parents', 'class'], true) ? 'portal/notices' : 'crud&res=notices'), $d['notice_type']);
        }
        if (!empty($flags['sms'])) {
            NoticeService::messageParents($d['audience'], $d['class_id'] ? (int)$d['class_id'] : null, $d['title'] . ': ' . mb_strimwidth($d['body'], 0, 250, '…'), $d['title']);
        }
    },
];
