<?php
declare(strict_types=1);

final class TransportController
{
    public function assign(): void
    {
        authorize('transport.manage');
        $classId = input_int('class_id');
        $routeId = input_int('route_id');
        $students = [];
        if ($classId || $routeId) {
            $w = ["s.status='active'"];
            $p = [];
            if ($classId) { $w[] = 's.class_id=?'; $p[] = $classId; }
            if ($routeId && !$classId) { $w[] = 's.route_id=?'; $p[] = $routeId; }
            if ($sec = input_int('section_id')) { $w[] = 's.section_id=?'; $p[] = $sec; }
            $students = DB::all('SELECT s.id, s.reg_no, s.first_name, s.last_name, s.route_id, s.address, c.name AS class_name, sec.name AS section_name FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id WHERE '
                . implode(' AND ', $w) . ' ORDER BY c.numeric_order, sec.name, s.roll_no+0, s.first_name', $p);
        }
        $routes = DB::all("SELECT r.*, v.vehicle_no, v.capacity, (SELECT COUNT(*) FROM students s WHERE s.route_id=r.id AND s.status='active') AS riders FROM transport_routes r LEFT JOIN transport_vehicles v ON v.id=r.vehicle_id ORDER BY r.name");
        view('transport/assign', compact('classId', 'routeId', 'students', 'routes') + ['title' => t('Assign Students to Routes')]);
    }

    public function save(): void
    {
        authorize('transport.manage');
        $valid = array_map('intval', DB::col('SELECT id FROM transport_routes'));
        $warn = [];
        $n = 0;
        DB::tx(function () use ($valid, &$n, &$warn) {
            foreach ((array)($_POST['route'] ?? []) as $sid => $rid) {
                $rid = (int)$rid ?: null;
                if ($rid !== null && !in_array($rid, $valid, true)) {
                    continue;
                }
                $old = DB::val('SELECT route_id FROM students WHERE id=?', [(int)$sid]);
                if ((int)$old === (int)$rid) {
                    continue;
                }
                DB::update('students', ['route_id' => $rid], 'id=?', [(int)$sid]);
                $n++;
            }
            foreach (DB::all("SELECT r.name, v.capacity, (SELECT COUNT(*) FROM students s WHERE s.route_id=r.id AND s.status='active') AS riders FROM transport_routes r JOIN transport_vehicles v ON v.id=r.vehicle_id") as $r) {
                if ($r['riders'] > $r['capacity']) {
                    $warn[] = "{$r['name']}: {$r['riders']}/{$r['capacity']}";
                }
            }
        });
        audit('assign', 'transport', null, "$n students updated");
        flash('success', sprintf(t('%d student route assignments updated.'), $n));
        if ($warn) {
            flash('warning', t('Vehicle over capacity') . ': ' . implode(', ', $warn));
        }
        back();
    }
}
