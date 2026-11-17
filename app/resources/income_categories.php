<?php
return [
    'table' => 'income_categories', 'title' => 'Income Categories', 'singular' => 'Income Category', 'icon' => 'bi-bookmark-plus', 'group' => 'Accounts',
    'perm' => ['view' => 'finance.manage', 'manage' => 'finance.manage'],
    'order' => 't.name', 'search' => ['t.name'],
    'columns' => ['name' => ['Name'], 'description' => ['Description']],
    'fields' => ['name' => ['label' => 'Name', 'rules' => 'required|max:100', 'unique' => true], 'description' => ['label' => 'Description', 'rules' => 'max:255']],
];
