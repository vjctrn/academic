<?php
// Allowed values for the dropdown fields
$borrowerTypes = ['Student', 'Staff', 'Faculty'];
$statuses = ['Borrowed', 'Returned', 'Overdue', 'Cancelled'];

// Queries that fill the dropdowns (they return an id and a label)
// The key is the column name of the field.
$dropdowns = [
    'borrower_id'  => "SELECT borrower_id AS id, borrower_no || ' - ' || full_name AS label FROM borrowers ORDER BY 2",
    'equipment_id' => "SELECT equipment_id AS id, property_no || ' - ' || equipment_name AS label FROM equipment ORDER BY 2",
];

// Each page that has an add/edit/delete screen. Settings:
//   table  = database table        key    = primary key column
//   parent = column linking to a parent record (only for the Items page)
//   fields = column => label, type, and min value (for numbers)
//            type can be: text, number, date, ref (dropdown from $dropdowns),
//            or an array of allowed values (dropdown)
//   list   = query for the table shown on the page
//   link   = optional link to a child page, [text, url start]
$pages = [
    'borrowers' => [
        'title'  => 'Borrowers',
        'table'  => 'borrowers',
        'key'    => 'borrower_id',
        'fields' => [
            'borrower_no'   => ['label' => 'Borrower No', 'type' => 'text'],
            'full_name'     => ['label' => 'Full Name', 'type' => 'text'],
            'borrower_type' => ['label' => 'Type', 'type' => $borrowerTypes],
        ],
        'list'   => 'SELECT borrower_id AS id, borrower_no, full_name, borrower_type
                     FROM borrowers ORDER BY borrower_id',
    ],

    'equipment' => [
        'title'  => 'Equipment',
        'table'  => 'equipment',
        'key'    => 'equipment_id',
        'fields' => [
            'property_no'        => ['label' => 'Property No', 'type' => 'text'],
            'equipment_name'     => ['label' => 'Name', 'type' => 'text'],
            'quantity_available' => ['label' => 'Qty Available', 'type' => 'number', 'min' => 0],
        ],
        'list'   => 'SELECT equipment_id AS id, property_no, equipment_name, quantity_available
                     FROM equipment ORDER BY equipment_id',
    ],

    'transactions' => [
        'title'  => 'Borrow Transactions',
        'table'  => 'borrow_transactions',
        'key'    => 'borrow_id',
        'fields' => [
            'borrower_id' => ['label' => 'Borrower', 'type' => 'ref'],
            'borrow_date' => ['label' => 'Borrow Date', 'type' => 'date'],
            'due_date'    => ['label' => 'Due Date', 'type' => 'date'],
            'status'      => ['label' => 'Status', 'type' => $statuses],
        ],
        'list'   => 'SELECT t.borrow_id AS id, b.full_name AS borrower, t.borrow_date, t.due_date, t.status
                     FROM borrow_transactions t
                     JOIN borrowers b ON b.borrower_id = t.borrower_id
                     ORDER BY t.borrow_id',
        'link'   => ['Items', '?p=items&borrow_id='],
    ],

    'items' => [
        'title'  => 'Borrow Items',
        'table'  => 'borrow_items',
        'key'    => 'borrow_item_id',
        'parent' => 'borrow_id',
        'fields' => [
            'equipment_id' => ['label' => 'Equipment', 'type' => 'ref'],
            'quantity'     => ['label' => 'Quantity', 'type' => 'number', 'min' => 1],
        ],
        'list'   => 'SELECT bi.borrow_item_id AS id, e.property_no, e.equipment_name, bi.quantity
                     FROM borrow_items bi
                     JOIN equipment e ON e.equipment_id = bi.equipment_id
                     WHERE bi.borrow_id = ?
                     ORDER BY bi.borrow_item_id',
    ],
];
