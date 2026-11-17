<?php
declare(strict_types=1);

final class HomeworkController
{
    public function index(): void
    {
        authorize('homework.view');
        $w = ['h.session_id=?'];
        $p = [session_id_current()];
        if ($c = input_int('class_id')) { $w[] = 'h.class_id=?'; $p[] = $c; }
        if ($s = input_int('subject_id')) { $w[] = 'h.subject_id=?'; $p[] = $s; }
        if (input('active')) { $w[] = 'h.due_date>=?'; $p[] = date('Y-m-d'); }
        if (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $w[] = '(h.section_id IN ' . DB::in($ids) . ' OR h.teacher_id=?)';
            $p = array_merge($p, $ids, [(int)(Auth::teacher()['id'] ?? 0)]);
        }
        if (($q = trim((string)input('q', ''))) !== '') { $w[] = 'h.title LIKE ?'; $p[] = "%$q%"; }
        $where = implode(' AND ', $w);
        $from = 'homework h JOIN classes c ON c.id=h.class_id LEFT JOIN sections sec ON sec.id=h.section_id JOIN subjects sb ON sb.id=h.subject_id LEFT JOIN teachers t ON t.id=h.teacher_id';
        $pg = paginate("SELECT COUNT(*) FROM $from WHERE $where", "SELECT h.*, c.name AS class_name, sec.name AS section_name, sb.name AS subject, t.name AS teacher,
            (SELECT COUNT(*) FROM homework_submissions hs WHERE hs.homework_id=h.id) AS submitted,
            (SELECT COUNT(*) FROM students st WHERE st.class_id=h.class_id AND st.status='active' AND (h.section_id IS NULL OR st.section_id=h.section_id)) AS strength
            FROM $from WHERE $where ORDER BY h.due_date DESC, h.id DESC", $p, 20);
        view('homework/index', ['p' => $pg, 'title' => t('Homework & Assignments')]);
    }

    private function find(int $id): array
    {
        $h = DB::row('SELECT h.*, c.name AS class_name, sec.name AS section_name, sb.name AS subject FROM homework h JOIN classes c ON c.id=h.class_id LEFT JOIN sections sec ON sec.id=h.section_id JOIN subjects sb ON sb.id=h.subject_id WHERE h.id=?', [$id]);
        if (!$h) {
            abort(404);
        }
        if (Auth::scoped() && !Auth::canSection($h['section_id'] ? (int)$h['section_id'] : null) && (int)$h['teacher_id'] !== (int)(Auth::teacher()['id'] ?? 0)) {
            abort(403);
        }
        return $h;
    }

    public function form(): void
    {
        authorize('homework.manage');
        $id = input_int('id');
        echo render('homework/form', ['h' => $id ? $this->find($id) : null], null);
    }

    public function save(): void
    {
        authorize('homework.manage');
        $id = input_int('id');
        $old = $id ? $this->find($id) : null;
        $d = ['class_id' => input_int('class_id'), 'section_id' => input_int('section_id'), 'subject_id' => input_int('subject_id'), 'title' => input('title'),
            'description' => input('description'), 'assign_date' => input('assign_date'), 'due_date' => input('due_date'), 'max_marks' => input('max_marks')];
        validate_or_fail($d, ['class_id' => 'required|exists:classes,id', 'section_id' => 'exists:sections,id', 'subject_id' => 'required|exists:subjects,id', 'title' => 'required|max:200',
            'description' => 'max:5000', 'assign_date' => 'required|date', 'due_date' => 'required|date', 'max_marks' => 'numeric|min:0|max:999']);
        if ($d['due_date'] < $d['assign_date']) {
            throw new ValidationException(['due_date' => 'Due date must be on or after the assign date.']);
        }
        if ($d['section_id'] && (int)DB::val('SELECT class_id FROM sections WHERE id=?', [$d['section_id']]) !== (int)$d['class_id']) {
            throw new ValidationException(['section_id' => 'Section does not belong to the class.']);
        }
        if (Auth::scoped()) {
            if (!$d['section_id'] || !Auth::canSubject((int)$d['section_id'], (int)$d['subject_id'])) {
                throw new ValidationException(['section_id' => 'You can only assign homework to your own sections and subjects.']);
            }
        }
        $d = nullify($d);
        if ($file = upload_file('attachment', 'homework', 'doc')) {
            $d['attachment'] = $file;
            if ($old) {
                delete_upload($old['attachment']);
            }
        }
        if ($id) {
            DB::update('homework', $d, 'id=?', [$id]);
        } else {
            $d += ['session_id' => session_id_current(), 'teacher_id' => Auth::teacher()['id'] ?? null, 'created_by' => Auth::id()];
            $id = DB::insert('homework', $d);
            // notify students & parents of the class/section
            $p = [$d['class_id']];
            $sql = "SELECT s.user_id, pr.user_id AS puid FROM students s LEFT JOIN parents pr ON pr.id=s.parent_id WHERE s.status='active' AND s.class_id=?";
            if ($d['section_id']) {
                $sql .= ' AND s.section_id=?';
                $p[] = $d['section_id'];
            }
            $uids = [];
            foreach (DB::all($sql, $p) as $r) {
                $uids[] = (int)$r['user_id'];
                $uids[] = (int)$r['puid'];
            }
            notify($uids, 'New homework: ' . $d['title'], DB::val('SELECT name FROM subjects WHERE id=?', [$d['subject_id']]) . ' · due ' . fmt_date($d['due_date']), 'index.php?r=portal/homework', 'homework');
        }
        audit($old ? 'update' : 'create', 'homework', (string)$id, $d['title']);
        json_out(['ok' => true, 'message' => t('Homework saved.')]);
    }

    public function delete(): void
    {
        authorize('homework.manage');
        $h = $this->find(input_int('id', 0));
        foreach (DB::col('SELECT attachment FROM homework_submissions WHERE homework_id=?', [$h['id']]) as $f) {
            delete_upload($f);
        }
        DB::delete('homework', 'id=?', [$h['id']]);
        delete_upload($h['attachment']);
        audit('delete', 'homework', (string)$h['id'], $h['title']);
        json_out(['ok' => true, 'message' => t('Deleted successfully')]);
    }

    public function submissions(): void
    {
        authorize('homework.view');
        $h = $this->find(input_int('id', 0));
        $p = [$h['id'], $h['class_id']];
        $sql = "SELECT hs.*, s.id AS student_id, s.reg_no, s.roll_no, s.first_name, s.last_name FROM students s LEFT JOIN homework_submissions hs ON hs.student_id=s.id AND hs.homework_id=?
                WHERE s.status='active' AND s.class_id=?";
        if ($h['section_id']) {
            $sql .= ' AND s.section_id=?';
            $p[] = $h['section_id'];
        }
        $rows = DB::all($sql . ' ORDER BY s.roll_no+0, s.first_name', $p);
        view('homework/submissions', ['h' => $h, 'rows' => $rows, 'title' => t('Submissions') . ': ' . $h['title']]);
    }

    /** Teacher feedback / marks; can also record an offline (copy) submission */
    public function review(): void
    {
        authorize('homework.manage');
        $h = $this->find(input_int('homework_id', 0));
        $n = 0;
        foreach ((array)($_POST['fb'] ?? []) as $sid => $f) {
            $sid = (int)$sid;
            $marks = trim((string)($f['marks'] ?? ''));
            $feedback = trim((string)($f['feedback'] ?? ''));
            $checked = !empty($f['checked']);
            if ($marks === '' && $feedback === '' && !$checked) {
                continue;
            }
            if ($marks !== '' && (!is_numeric($marks) || (float)$marks < 0 || ($h['max_marks'] && (float)$marks > (float)$h['max_marks']))) {
                throw new ValidationException(['fb' => "Invalid marks '$marks'."]);
            }
            $exists = DB::row('SELECT id, submitted_at FROM homework_submissions WHERE homework_id=? AND student_id=?', [$h['id'], $sid]);
            $data = ['marks' => $marks === '' ? null : (float)$marks, 'feedback' => mb_substr($feedback, 0, 2000) ?: null, 'status' => 'reviewed', 'reviewed_by' => Auth::id(), 'reviewed_at' => date('Y-m-d H:i:s')];
            if ($exists) {
                DB::update('homework_submissions', $data, 'id=?', [$exists['id']]);
            } elseif (DB::val("SELECT id FROM students WHERE id=? AND class_id=? AND status='active'", [$sid, $h['class_id']])) {
                DB::insert('homework_submissions', $data + ['homework_id' => $h['id'], 'student_id' => $sid, 'submitted_at' => date('Y-m-d H:i:s'), 'content' => 'Submitted in class (recorded by teacher)']);
            } else {
                continue;
            }
            $n++;
        }
        audit('review', 'homework', (string)$h['id'], "$n reviewed");
        flash('success', sprintf(t('%d submissions reviewed.'), $n));
        redirect(url('homework/submissions', ['id' => $h['id']]));
    }
}
