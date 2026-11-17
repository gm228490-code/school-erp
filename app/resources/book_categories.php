<?php
return [
    'table' => 'book_categories', 'title' => 'Book Categories', 'singular' => 'Book Category', 'icon' => 'bi-collection', 'group' => 'Library',
    'perm' => ['view' => 'library.view', 'manage' => 'library.manage'],
    'order' => 't.name', 'search' => ['t.name'],
    'columns' => ['name' => ['Name']],
    'fields' => ['name' => ['label' => 'Name', 'rules' => 'required|max:100', 'unique' => true]],
];
