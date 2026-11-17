<?php
return [
    'table' => 'transport_vehicles', 'title' => 'Vehicles', 'singular' => 'Vehicle', 'icon' => 'bi-bus-front', 'group' => 'Transport',
    'perm' => ['view' => 'transport.view', 'manage' => 'transport.manage'],
    'select' => "t.*, (SELECT COUNT(*) FROM students s JOIN transport_routes r ON r.id=s.route_id WHERE r.vehicle_id=t.id AND s.status='active') AS riders",
    'order' => 't.vehicle_no', 'search' => ['t.vehicle_no', 't.model', 't.driver_name', 't.driver_phone'],
    'filters' => ['status' => ['label' => 'Status', 'sql' => 't.status', 'options' => ['active' => 'Active', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive']]],
    'columns' => [
        'vehicle_no' => ['Vehicle No.', 'render' => fn($r) => '<span class="fw-semibold">' . e($r['vehicle_no']) . '</span><div class="small text-muted">' . e($r['model']) . '</div>'],
        'driver_name' => ['Driver'], 'driver_phone' => ['Driver Phone'], 'driver_license' => ['License'],
        'riders' => ['Occupancy', 'sort' => 'riders', 'render' => function ($r) {
            $pct = $r['capacity'] ? min(100, round($r['riders'] / $r['capacity'] * 100)) : 0;
            return '<div class="small">' . (int)$r['riders'] . ' / ' . (int)$r['capacity'] . '</div><div class="progress progress-thin" style="width:90px"><div class="progress-bar ' . ($pct >= 100 ? 'bg-danger' : '') . '" style="width:' . $pct . '%"></div></div>';
        }],
        'status' => ['Status', 'type' => 'badge'],
    ],
    'fields' => [
        'vehicle_no' => ['label' => 'Vehicle Number', 'rules' => 'required|max:30', 'unique' => true, 'placeholder' => 'e.g. LEA-1234'],
        'model' => ['label' => 'Model / Type', 'rules' => 'max:100', 'placeholder' => 'e.g. Toyota Coaster 2019'],
        'capacity' => ['label' => 'Seating Capacity', 'type' => 'number', 'rules' => 'required|integer|min:1|max:200', 'default' => 30],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive'], 'rules' => 'required', 'default' => 'active'],
        'h1' => ['label' => 'Driver Details', 'type' => 'heading'],
        'driver_name' => ['label' => 'Driver Name', 'rules' => 'max:120'],
        'driver_phone' => ['label' => 'Driver Phone', 'rules' => 'phone|max:30'],
        'driver_cnic' => ['label' => 'Driver CNIC', 'rules' => 'cnic'],
        'driver_license' => ['label' => 'Driving License No.', 'rules' => 'max:50'],
    ],
];
