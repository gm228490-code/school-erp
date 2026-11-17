<?php
return [
    'table' => 'transport_routes', 'title' => 'Transport Routes', 'singular' => 'Route', 'icon' => 'bi-signpost-split', 'group' => 'Transport',
    'perm' => ['view' => 'transport.view', 'manage' => 'transport.manage'],
    'from' => 'transport_routes t LEFT JOIN transport_vehicles v ON v.id=t.vehicle_id',
    'select' => "t.*, v.vehicle_no, v.capacity, v.driver_name, (SELECT COUNT(*) FROM students s WHERE s.route_id=t.id AND s.status='active') AS riders",
    'order' => 't.name', 'search' => ['t.name', 't.start_point', 't.end_point', 't.stops', 'v.vehicle_no'],
    'columns' => [
        'name' => ['Route', 'render' => fn($r) => '<span class="fw-semibold">' . e($r['name']) . '</span><div class="small text-muted">' . e($r['start_point'] . ' → ' . $r['end_point']) . '</div>'],
        'stops' => ['Stops'], 'vehicle_no' => ['Vehicle', 'sort' => 'v.vehicle_no', 'render' => fn($r) => e($r['vehicle_no']) . ($r['driver_name'] ? '<div class="small text-muted">' . e($r['driver_name']) . '</div>' : '')],
        'monthly_fee' => ['Monthly Fee', 'type' => 'money'],
        'riders' => ['Students', 'sort' => 'riders', 'render' => fn($r) => '<a href="' . e(url('transport/assign', ['route_id' => $r['id']])) . '">' . (int)$r['riders'] . '</a>'],
    ],
    'fields' => [
        'name' => ['label' => 'Route Name', 'rules' => 'required|max:100', 'unique' => true],
        'vehicle_id' => ['label' => 'Vehicle', 'type' => 'select', 'options' => fn() => DB::pairs("SELECT id, CONCAT(vehicle_no, ' (', capacity, ' seats)') FROM transport_vehicles ORDER BY vehicle_no")],
        'start_point' => ['label' => 'Start Point', 'rules' => 'max:150'],
        'end_point' => ['label' => 'End Point', 'rules' => 'max:150'],
        'monthly_fee' => ['label' => 'Monthly Transport Fee', 'type' => 'decimal', 'rules' => 'required|numeric|min:0', 'help' => 'Added to monthly invoices of students on this route.'],
        'stops' => ['label' => 'Stops (comma separated)', 'type' => 'textarea', 'col' => 12, 'rows' => 2],
    ],
];
