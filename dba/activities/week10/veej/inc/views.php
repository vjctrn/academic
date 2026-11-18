<?php
// Get the choices for a dropdown: a fixed list, or rows from the database
function getOptions($type, $name) {
    global $db, $dropdowns;
    if (is_array($type)) {
        return array_combine($type, $type);
    }
    return array_column($db->query($dropdowns[$name])->fetchAll(), 'label', 'id');
}

// Build a <select> with the current value already selected
function makeSelect($name, $type, $current = '') {
    $html = '<select name="' . h($name) . '" required><option value="">-- choose --</option>';
    foreach (getOptions($type, $name) as $value => $label) {
        $selected = ((string)$value === (string)$current) ? ' selected' : '';
        $html .= '<option value="' . h($value) . '"' . $selected . '>' . h($label) . '</option>';
    }
    return $html . '</select>';
}

// Build the form control for one field
function makeInput($name, $field, $current) {
    $type = $field['type'];

    if ($type === 'ref' || is_array($type)) {
        return makeSelect($name, $type, $current);
    }
    $min = ($type === 'number') ? ' min="' . ($field['min'] ?? 0) . '"' : '';
    return '<input type="' . $type . '" name="' . h($name) . '" value="' . h($current) . '" required' . $min . '>';
}

// Build an HTML table from rows.
// $actions (optional) is a function that returns the buttons/links for each row.
function makeTable($rows, $actions = null) {
    if (!$rows) {
        return '<p><i>No rows.</i></p>';
    }

    // Header row uses the column names
    $html = '<table><tr>';
    foreach (array_keys($rows[0]) as $column) {
        $html .= '<th>' . h($column) . '</th>';
    }
    $html .= ($actions ? '<th></th>' : '') . '</tr>';

    // One table row per database row
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            if ($value === null) {
                $html .= '<td>&mdash;</td>';
            } elseif (is_bool($value)) {
                $html .= '<td>' . ($value ? 'Yes' : 'No') . '</td>';
            } else {
                $html .= '<td>' . h($value) . '</td>';
            }
        }
        $html .= ($actions ? '<td>' . $actions($row) . '</td>' : '') . '</tr>';
    }
    return $html . '</table>';
}

// A full add/edit/delete page: form on top, table below
function showCrudPage($page, $name) {
    $parent   = $page['parent'] ?? null;
    $parentId = $parent ? ($_GET[$parent] ?? '') : '';

    echo '<h2>' . h($page['title']);
    if ($parent) {
        echo ' for transaction #' . h($parentId) . ' <small><a href="?p=transactions">&larr; back</a></small>';
    }
    echo '</h2>';

    // The Items page only works when opened from a transaction
    if ($parent && !ctype_digit($parentId)) {
        echo '<p class="err">Open this page from the Transactions list.</p>';
        return;
    }
    $base = "?p=$name" . ($parent ? "&$parent=" . urlencode($parentId) : '');

    // If ?edit=ID is in the URL, load that row to fill the form
    $row = [];
    $editId = $_GET['edit'] ?? '';
    if ($editId !== '') {
        $sql = "SELECT * FROM {$page['table']} WHERE {$page['key']} = ?";
        $params = [$editId];
        if ($parent) {
            $sql .= " AND $parent = ?";
            $params[] = $parentId;
        }
        $row = ctype_digit($editId) ? q($sql, $params)->fetch() : false;
        if (!$row) {
            echo '<p class="err">Record not found.</p>';
            $row = [];
            $editId = '';
        }
    }

    // Add / edit form
    echo '<form method="post"><input type="hidden" name="id" value="' . h($editId) . '">';
    foreach ($page['fields'] as $column => $field) {
        echo '<label>' . h($field['label']) . ' ' . makeInput($column, $field, $row[$column] ?? '') . '</label> ';
    }
    echo '<button>' . ($editId === '' ? 'Add' : 'Update') . '</button>';
    if ($editId !== '') {
        echo ' <a href="' . $base . '">Cancel</a>';
    }
    echo '</form>';

    // Table with Edit / Delete (and the child page link if there is one)
    $rows = q($page['list'], $parent ? [$parentId] : [])->fetchAll();
    echo makeTable($rows, function ($r) use ($page, $base) {
        $html = '';
        if (isset($page['link'])) {
            $html .= '<a href="' . $page['link'][1] . $r['id'] . '">' . $page['link'][0] . '</a> ';
        }
        $html .= '<a href="' . $base . '&edit=' . $r['id'] . '">Edit</a> ';
        $html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Delete this record?\')">'
               . '<button name="delete" value="' . $r['id'] . '">Delete</button></form>';
        return $html;
    });
}

// The New Borrowing form: one header and up to 3 items
function showBorrowForm() {
    $today = date('Y-m-d');
    $nextWeek = date('Y-m-d', strtotime('+7 days')); // default due date

    echo '<h2>New Borrowing</h2>';
    echo '<p>Saves the borrowing, its items, and subtracts the borrowed quantity from the equipment stock, all in one database transaction.</p>';
    echo '<form method="post">';
    echo '<label>Borrower ' . makeSelect('borrower_id', 'ref') . '</label> ';
    echo '<label>Borrow Date <input type="date" name="borrow_date" value="' . $today . '" required></label> ';
    echo '<label>Due Date <input type="date" name="due_date" value="' . $nextWeek . '" required></label><br><br>';

    // Three item rows (equipment dropdown + quantity)
    $equipment = getOptions('ref', 'equipment_id');
    for ($i = 1; $i <= 3; $i++) {
        echo "Item $i: <select name=\"eq[]\"><option value=\"\">-- none --</option>";
        foreach ($equipment as $value => $label) {
            echo '<option value="' . h($value) . '">' . h($label) . '</option>';
        }
        echo '</select> Qty <input type="number" name="qty[]" min="1" style="width:5em"><br>';
    }

    echo '<br><label><input type="checkbox" name="fail"> Simulate a failure (demo of ROLLBACK)</label><br><br>';
    echo '<button>Record Borrowing</button></form>';
}
