<?php
return [
    'table' => 'expense_categories', 'title' => 'Expense Categories', 'singular' => 'Expense Category', 'icon' => 'bi-bookmark-dash', 'group' => 'Accounts',
    'perm' => ['view' => 'finance.manage', 'manage' => 'finance.manage'],
    'order' => 't.name', 'search' => ['t.name'],
    'columns' => ['name' => ['Name'], 'description' => ['Description']],
    'fields' => ['name' => ['label' => 'Name', 'rules' => 'required|max:100', 'unique' => true], 'description' => ['label' => 'Description', 'rules' => 'max:255']],
];
