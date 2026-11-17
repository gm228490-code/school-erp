<?php
$methods = ['cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque', 'online' => 'Online'];
return [
    'table' => 'incomes', 'title' => 'Income', 'singular' => 'Income', 'icon' => 'bi-box-arrow-in-down', 'group' => 'Accounts',
    'perm' => ['view' => 'finance.view', 'manage' => 'finance.manage'], 'stamp' => ['created_by'],
    'intro' => 'Other income (donations, canteen, etc.). Fee collections are recorded automatically and are not entered here.',
    'from' => 'incomes t JOIN income_categories ic ON ic.id=t.category_id', 'select' => 't.*, ic.name AS cat_name',
    'order' => 't.income_date DESC, t.id DESC', 'search' => ['t.title', 't.reference', 'ic.name'],
    'filters' => [
        'cat' => ['label' => 'Category', 'sql' => 't.category_id', 'options' => fn() => DB::pairs('SELECT id,name FROM income_categories ORDER BY name')],
        'from' => ['label' => 'From', 'type' => 'date_from', 'sql' => 't.income_date'],
        'to' => ['label' => 'To', 'type' => 'date_to', 'sql' => 't.income_date'],
    ],
    'summary' => function () {
        [$w, $p] = Crud::load('incomes')->where();
        return e(t('Total')) . ': ' . e(money(DB::val("SELECT COALESCE(SUM(t.amount),0) FROM incomes t JOIN income_categories ic ON ic.id=t.category_id WHERE $w", $p)));
    },
    'columns' => [
        'income_date' => ['Date', 'type' => 'date'], 'title' => ['Title'], 'cat_name' => ['Category', 'sort' => 'ic.name'],
        'method' => ['Method', 'render' => fn($r) => e(t(ucfirst($r['method'])))], 'reference' => ['Reference'], 'amount' => ['Amount', 'type' => 'money'],
    ],
    'fields' => [
        'title' => ['label' => 'Title', 'rules' => 'required|max:150'],
        'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM income_categories ORDER BY name'), 'rules' => 'required'],
        'amount' => ['label' => 'Amount', 'type' => 'decimal', 'rules' => 'required|numeric|min:1'],
        'income_date' => ['label' => 'Date', 'type' => 'date', 'rules' => 'required', 'default' => date('Y-m-d')],
        'method' => ['label' => 'Payment Method', 'type' => 'select', 'options' => $methods, 'rules' => 'required', 'default' => 'cash'],
        'reference' => ['label' => 'Reference No.', 'rules' => 'max:100'],
        'description' => ['label' => 'Description', 'type' => 'textarea', 'col' => 12],
    ],
];
