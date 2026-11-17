<?php
return [
    'table' => 'leaves', 'title' => 'Leave Management', 'singular' => 'Leave', 'icon' => 'bi-calendar-x', 'group' => 'Human Resource',
    'perm' => ['view' => 'leaves.view', 'manage' => 'leaves.manage'],
    'select' => "t.*, CONCAT(t.emp_type,':',t.emp_id) AS employee, " . employee_name_sql() . ' AS emp_name',
    'order' => 't.id DESC', 'search' => [employee_name_sql(), 't.reason'],
    'filters' => [
        'status' => ['label' => 'Status', 'sql' => 't.status', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']],
        'type' => ['label' => 'Employee Type', 'sql' => 't.emp_type', 'options' => ['teacher' => 'Teacher', 'staff' => 'Staff']],
        'from' => ['label' => 'From', 'type' => 'date_from', 'sql' => 't.from_date'],
    ],
    'columns' => [
        'emp_name' => ['Employee', 'sort' => 'emp_name', 'render' => fn($r) => '<div class="fw-semibold">' . e($r['emp_name']) . '</div><div class="small text-muted">' . e(t(ucfirst($r['emp_type']))) . '</div>'],
        'leave_type' => ['Type', 'render' => fn($r) => e(t(ucfirst($r['leave_type'])))],
        'from_date' => ['From', 'type' => 'date'], 'to_date' => ['To', 'type' => 'date'], 'days' => ['Days'],
        'reason' => ['Reason'], 'status' => ['Status', 'type' => 'badge'],
    ],
    'fields' => [
        'employee' => ['label' => 'Employee', 'type' => 'select', 'options' => fn() => employee_options(), 'rules' => 'required', 'col' => 12],
        'leave_type' => ['label' => 'Leave Type', 'type' => 'select', 'options' => ['casual' => 'Casual', 'sick' => 'Sick', 'annual' => 'Annual', 'maternity' => 'Maternity', 'unpaid' => 'Unpaid', 'other' => 'Other'], 'rules' => 'required', 'col' => 4],
        'from_date' => ['label' => 'From', 'type' => 'date', 'rules' => 'required', 'col' => 4],
        'to_date' => ['label' => 'To', 'type' => 'date', 'rules' => 'required', 'col' => 4],
        'reason' => ['label' => 'Reason', 'type' => 'textarea', 'col' => 12, 'rules' => 'max:1000'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], 'rules' => 'required', 'default' => 'pending'],
    ],
    'before_save' => function ($d, $id, $old) {
        $d = split_employee($d);
        if ($d['to_date'] < $d['from_date']) {
            throw new ValidationException(['to_date' => 'End date must be on or after start date.']);
        }
        $d['days'] = day_count($d['from_date'], $d['to_date']);
        if ($d['status'] !== 'pending' && (!$old || $old['status'] !== $d['status'])) {
            $d['approved_by'] = Auth::id();
            $d['approved_at'] = date('Y-m-d H:i:s');
        }
        return $d;
    },
    'after_save' => function ($id, $d) {
        // Approved leave is reflected in employee attendance
        if ($d['status'] === 'approved') {
            for ($t = strtotime($d['from_date']); $t <= strtotime($d['to_date']); $t += 86400) {
                DB::q("INSERT INTO employee_attendance (emp_type, emp_id, att_date, status, remarks, marked_by) VALUES (?,?,?,'leave','Approved leave',?)
                       ON DUPLICATE KEY UPDATE status='leave', remarks='Approved leave'", [$d['emp_type'], $d['emp_id'], date('Y-m-d', $t), Auth::id()]);
            }
        }
        $emp = employee($d['emp_type'], (int)$d['emp_id']);
        if ($emp && $emp['user_id'] && $d['status'] !== 'pending') {
            notify([$emp['user_id']], 'Leave ' . $d['status'], 'Your leave from ' . fmt_date($d['from_date']) . ' to ' . fmt_date($d['to_date']) . ' has been ' . $d['status'] . '.', '', 'leave');
        }
    },
    'row_actions' => fn($r) => $r['status'] === 'pending' && can('leaves.manage')
        ? '<button class="btn btn-sm btn-outline-success" title="' . e(t('Approve')) . '" data-post="' . e(url('hr/leave_status')) . '" data-field-id="' . (int)$r['id'] . '" data-field-status="approved"><i class="bi bi-check-lg"></i></button> '
        . '<button class="btn btn-sm btn-outline-danger" title="' . e(t('Reject')) . '" data-post="' . e(url('hr/leave_status')) . '" data-field-id="' . (int)$r['id'] . '" data-field-status="rejected"><i class="bi bi-x-lg"></i></button> ' : '',
];
