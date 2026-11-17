<?php
declare(strict_types=1);

final class TimetableController
{
    public function index(): void
    {
        authorize('timetable.view');
        $sectionId = input_int('section_id');
        if (Auth::scoped() && !$sectionId && ($t = Auth::teacher())) {
            redirect(url('timetable/teacher', ['teacher_id' => $t['id']]));
        }
        $grid = [];
        $section = null;
        if ($sectionId) {
            $section = DB::row('SELECT s.*, c.name AS class_name, c.id AS class_id FROM sections s JOIN classes c ON c.id=s.class_id WHERE s.id=?', [$sectionId]);
            if (!$section) {
                abort(404);
            }
            foreach (DB::all('SELECT tt.*, sb.name AS subject, t.name AS teacher FROM timetables tt LEFT JOIN subjects sb ON sb.id=tt.subject_id LEFT JOIN teachers t ON t.id=tt.teacher_id WHERE tt.section_id=? AND tt.session_id=?', [$sectionId, session_id_current()]) as $r) {
                $grid[$r['day_of_week']][$r['period_id']] = $r;
            }
        }
        $periods = DB::all('SELECT * FROM periods ORDER BY sort_order, start_time');
        $data = ['section' => $section, 'sectionId' => $sectionId, 'grid' => $grid, 'periods' => $periods, 'days' => array_intersect_key(days_of_week(), array_flip(school_days())), 'title' => t('Class Timetable')];
        if ($section && input('print')) {
            view('timetable/print', $data + ['landscape' => true, 'subtitle' => $section['class_name'] . ' - ' . $section['name'] . ' · ' . (current_session()['name'] ?? '')], 'print');
        }
        view('timetable/index', $data);
    }

    public function save(): void
    {
        authorize('timetable.manage');
        $sectionId = input_int('section_id', 0);
        if (!DB::val('SELECT id FROM sections WHERE id=?', [$sectionId])) {
            abort(404);
        }
        $sid = session_id_current();
        $breaks = array_map('intval', DB::col('SELECT id FROM periods WHERE is_break=1'));
        $validPeriods = array_map('intval', DB::col('SELECT id FROM periods'));
        $clashes = [];
        DB::tx(function () use ($sectionId, $sid, $breaks, $validPeriods, &$clashes) {
            foreach ((array)($_POST['slot'] ?? []) as $day => $periods) {
                $day = (int)$day;
                if ($day < 1 || $day > 7) {
                    continue;
                }
                foreach ((array)$periods as $pid => $v) {
                    $pid = (int)$pid;
                    if (!in_array($pid, $validPeriods, true) || in_array($pid, $breaks, true)) {
                        continue;
                    }
                    $subject = (int)($v['subject'] ?? 0) ?: null;
                    $teacher = (int)($v['teacher'] ?? 0) ?: null;
                    $room = mb_substr(trim((string)($v['room'] ?? '')), 0, 30) ?: null;
                    if (!$subject && !$teacher) {
                        DB::q('DELETE FROM timetables WHERE session_id=? AND section_id=? AND day_of_week=? AND period_id=?', [$sid, $sectionId, $day, $pid]);
                        continue;
                    }
                    if ($teacher) {
                        $busy = DB::row('SELECT c.name AS cls, s.name AS sec FROM timetables tt JOIN sections s ON s.id=tt.section_id JOIN classes c ON c.id=s.class_id WHERE tt.session_id=? AND tt.teacher_id=? AND tt.day_of_week=? AND tt.period_id=? AND tt.section_id<>?', [$sid, $teacher, $day, $pid, $sectionId]);
                        if ($busy) {
                            $clashes[] = days_of_week()[$day] . ' / ' . DB::val('SELECT name FROM periods WHERE id=?', [$pid]) . ': ' . DB::val('SELECT name FROM teachers WHERE id=?', [$teacher]) . ' already teaches ' . $busy['cls'] . ' ' . $busy['sec'];
                            continue;
                        }
                    }
                    DB::upsert('timetables', ['session_id' => $sid, 'section_id' => $sectionId, 'day_of_week' => $day, 'period_id' => $pid, 'subject_id' => $subject, 'teacher_id' => $teacher, 'room' => $room], ['subject_id', 'teacher_id', 'room']);
                }
            }
        });
        audit('update', 'timetables', (string)$sectionId);
        flash($clashes ? 'warning' : 'success', $clashes ? t('Timetable saved with conflicts skipped:') . ' ' . implode('; ', $clashes) : t('Timetable saved.'));
        redirect(url('timetable', ['section_id' => $sectionId]));
    }

    public function teacher(): void
    {
        authorize('timetable.view');
        $teacherId = input_int('teacher_id');
        if (Auth::scoped()) {
            $teacherId = (int)(Auth::teacher()['id'] ?? 0);
        }
        $grid = [];
        $teacher = $teacherId ? DB::row('SELECT * FROM teachers WHERE id=?', [$teacherId]) : null;
        if ($teacher) {
            foreach (DB::all('SELECT tt.*, sb.name AS subject, c.name AS class_name, s.name AS section_name FROM timetables tt JOIN sections s ON s.id=tt.section_id JOIN classes c ON c.id=s.class_id LEFT JOIN subjects sb ON sb.id=tt.subject_id WHERE tt.teacher_id=? AND tt.session_id=?', [$teacherId, session_id_current()]) as $r) {
                $grid[$r['day_of_week']][$r['period_id']] = $r;
            }
        }
        $data = ['teacher' => $teacher, 'teacherId' => $teacherId, 'grid' => $grid, 'periods' => DB::all('SELECT * FROM periods ORDER BY sort_order, start_time'),
            'days' => array_intersect_key(days_of_week(), array_flip(school_days())), 'title' => t('Teacher Timetable')];
        if ($teacher && input('print')) {
            view('timetable/print', $data + ['landscape' => true, 'subtitle' => $teacher['name'] . ' (' . $teacher['employee_no'] . ')', 'isTeacher' => true], 'print');
        }
        view('timetable/teacher', $data);
    }
}
