<?php
declare(strict_types=1);

final class HrController
{
    /** Approve / reject a leave request (AJAX) */
    public function leave_status(): void
    {
        authorize('leaves.manage');
        $status = (string)input('status');
        if (!in_array($status, ['approved', 'rejected'], true)) {
            abort(400, 'Invalid status');
        }
        $l = DB::row('SELECT * FROM leaves WHERE id=?', [input_int('id', 0)]);
        if (!$l) {
            abort(404);
        }
        DB::tx(function () use ($l, $status) {
            DB::update('leaves', ['status' => $status, 'approved_by' => Auth::id(), 'approved_at' => date('Y-m-d H:i:s')], 'id=?', [$l['id']]);
            if ($status === 'approved') {
                for ($t = strtotime($l['from_date']); $t <= strtotime($l['to_date']); $t += 86400) {
                    DB::q("INSERT INTO employee_attendance (emp_type, emp_id, att_date, status, remarks, marked_by) VALUES (?,?,?,'leave','Approved leave',?)
                           ON DUPLICATE KEY UPDATE status='leave', remarks='Approved leave'", [$l['emp_type'], $l['emp_id'], date('Y-m-d', $t), Auth::id()]);
                }
            }
        });
        $emp = employee($l['emp_type'], (int)$l['emp_id']);
        if ($emp && $emp['user_id']) {
            notify([$emp['user_id']], 'Leave ' . $status, 'Your leave from ' . fmt_date($l['from_date']) . ' to ' . fmt_date($l['to_date']) . " has been $status.", '', 'leave');
        }
        audit($status, 'leaves', (string)$l['id']);
        json_out(['ok' => true, 'message' => t('Leave ' . $status . '.')]);
    }
}
