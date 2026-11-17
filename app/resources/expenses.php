<?php
$methods = ['cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque', 'online' => 'Online'];
return [
    'table' => 'expenses', 'title' => 'Expenses', 'singular' => 'Expense', 'icon' => 'bi-box-arrow-up', 'group' => 'Accounts',
    'perm' => ['view' => 'finance.view', 'manage' => 'finance.manage'], 'stamp' => ['created_by'],
    'intro' => 'Utility bills, maintenance and other expenses. Salary payments are recorded from the Salaries module.',
    'from' => 'expenses t JOIN expense_categories ec ON ec.id=t.category_id', 'select' => 't.*, ec.name AS cat_name',
    'order' => 't.expense_date DESC, t.id DESC', 'search' => ['t.title', 't.paid_to', 't.reference', 'ec.name'],
    'filters' => [
        'cat' => ['label' => 'Category', 'sql' => 't.category_id', 'options' => fn() => DB::pairs('SELECT id,name FROM expense_categories ORDER BY name')],
        'from' => ['label' => 'From', 'type' => 'date_from', 'sql' => 't.expense_date'],
        'to' => ['label' => 'To', 'type' => 'date_to', 'sql' => 't.expense_date'],
    ],
    'summary' => function () {
        [$w, $p] = Crud::load('expenses')->where();
        return e(t('Total')) . ': ' . e(money(DB::val("SELECT COALESCE(SUM(t.amount),0) FROM expenses t JOIN expense_categories ec ON ec.id=t.category_id WHERE $w", $p)));
    },
    'columns' => [
        'expense_date' => ['Date', 'type' => 'date'], 'title' => ['Title'], 'cat_name' => ['Category', 'sort' => 'ec.name'], 'paid_to' => ['Paid To'],
        'method' => ['Method', 'render' => fn($r) => e(t(ucfirst($r['method'])))], 'amount' => ['Amount', 'type' => 'money'], 'attachment' => ['Bill', 'type' => 'file', 'sort' => false, 'noexport' => true],
    ],
    'fields' => [
        'title' => ['label' => 'Title', 'rules' => 'required|max:150'],
        'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM expense_categories ORDER BY name'), 'rules' => 'required'],
        'amount' => ['label' => 'Amount', 'type' => 'decimal', 'rules' => 'required|numeric|min:1'],
        'expense_date' => ['label' => 'Date', 'type' => 'date', 'rules' => 'required', 'default' => date('Y-m-d')],
        'paid_to' => ['label' => 'Paid To', 'rules' => 'max:150'],
        'method' => ['label' => 'Payment Method', 'type' => 'select', 'options' => $methods, 'rules' => 'required', 'default' => 'cash'],
        'reference' => ['label' => 'Reference / Bill No.', 'rules' => 'max:100'],
        'attachment' => ['label' => 'Bill / Receipt (optional)', 'type' => 'file', 'dir' => 'expenses'],
        'description' => ['label' => 'Description', 'type' => 'textarea', 'col' => 12],
    ],
];
