<?php
return [
    'table' => 'books', 'title' => 'Books', 'singular' => 'Book', 'icon' => 'bi-book-half', 'group' => 'Library',
    'perm' => ['view' => 'library.view', 'manage' => 'library.manage'],
    'from' => 'books t LEFT JOIN book_categories bc ON bc.id=t.category_id', 'select' => 't.*, bc.name AS cat_name',
    'order' => 't.title', 'search' => ['t.title', 't.isbn', 't.author', 't.publisher', 't.rack_no'],
    'filters' => [
        'cat' => ['label' => 'Category', 'sql' => 't.category_id', 'options' => fn() => DB::pairs('SELECT id,name FROM book_categories ORDER BY name')],
    ],
    'summary' => fn() => e(t('Titles')) . ': ' . (int)DB::val('SELECT COUNT(*) FROM books') . ' · ' . e(t('Copies')) . ': ' . (int)DB::val('SELECT COALESCE(SUM(quantity),0) FROM books') . ' · ' . e(t('Issued')) . ': ' . (int)DB::val("SELECT COUNT(*) FROM book_issues WHERE status='issued'"),
    'columns' => [
        'title' => ['Title', 'render' => fn($r) => '<div class="fw-semibold">' . e($r['title']) . '</div><div class="small text-muted">' . e(trim(($r['edition'] ? $r['edition'] . ' ed. ' : '') . ($r['publish_year'] ?: ''))) . '</div>'],
        'isbn' => ['ISBN'], 'author' => ['Author'], 'publisher' => ['Publisher'], 'cat_name' => ['Category', 'sort' => 'bc.name'], 'rack_no' => ['Rack'],
        'available' => ['Available', 'render' => fn($r) => '<span class="badge text-bg-' . ($r['available'] > 0 ? 'success' : 'danger') . ' badge-soft">' . (int)$r['available'] . ' / ' . (int)$r['quantity'] . '</span>'],
        'price' => ['Price', 'type' => 'money'],
    ],
    'fields' => [
        'title' => ['label' => 'Book Title', 'rules' => 'required|max:200', 'col' => 8],
        'isbn' => ['label' => 'ISBN', 'rules' => 'max:20', 'unique' => true, 'col' => 4],
        'author' => ['label' => 'Author', 'rules' => 'max:150'],
        'publisher' => ['label' => 'Publisher', 'rules' => 'max:150'],
        'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => fn() => DB::pairs('SELECT id,name FROM book_categories ORDER BY name'), 'col' => 4],
        'edition' => ['label' => 'Edition', 'rules' => 'max:50', 'col' => 4],
        'publish_year' => ['label' => 'Publish Year', 'type' => 'number', 'rules' => 'integer|min:1800|max:2100', 'col' => 4],
        'quantity' => ['label' => 'Quantity (copies)', 'type' => 'number', 'rules' => 'required|integer|min:1', 'default' => 1, 'col' => 4],
        'rack_no' => ['label' => 'Rack No.', 'rules' => 'max:30', 'col' => 4],
        'price' => ['label' => 'Price', 'type' => 'decimal', 'rules' => 'numeric|min:0', 'col' => 4],
    ],
    'before_save' => function ($d, $id, $old) {
        $issued = $id ? (int)DB::val("SELECT COUNT(*) FROM book_issues WHERE book_id=? AND status='issued'", [$id]) : 0;
        if ((int)$d['quantity'] < $issued) {
            throw new ValidationException(['quantity' => "Quantity cannot be less than currently issued copies ($issued)."]);
        }
        $d['available'] = (int)$d['quantity'] - $issued;
        return $d;
    },
    'row_actions' => fn($r) => $r['available'] > 0 && can('library.issue') ? '<button class="btn btn-sm btn-outline-primary" title="' . e(t('Issue')) . '" data-modal="' . e(url('library/issue_form', ['book_id' => $r['id']])) . '"><i class="bi bi-box-arrow-right"></i></button> ' : '',
    'before_delete' => fn($id) => DB::val('SELECT COUNT(*) FROM book_issues WHERE book_id=?', [$id]) ? 'Book has issue history and cannot be deleted. Reduce the quantity instead.' : null,
];
