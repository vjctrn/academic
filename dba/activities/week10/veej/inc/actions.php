<?php
// Check every field in $fields using the values in $data.
// Returns the cleaned values, or stops with an error message.
function validate($fields, $data) {
    $clean = [];

    foreach ($fields as $name => $field) {
        $label = $field['label'];
        $type  = $field['type'];
        $value = trim($data[$name] ?? '');

        if ($value === '') {
            bad("$label is required.");
        }

        if ($type === 'number') {
            $min = $field['min'] ?? 0;
            if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]) === false) {
                bad("$label must be a whole number of at least $min.");
            }
        } elseif ($type === 'date') {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                bad("$label is not a valid date.");
            }
        } elseif ($type === 'ref') {
            if (!ctype_digit($value)) {
                bad("Choose a valid $label.");
            }
        } elseif (is_array($type)) {
            if (!in_array($value, $type, true)) {
                bad("Invalid $label.");
            }
        } elseif (strlen($value) > 100) {
            bad("$label is too long.");
        }

        $clean[$name] = $value;
    }
    return $clean;
}

// Add a new row (no id posted) or update a row (id posted)
function savePage($page) {
    $parent   = $page['parent'] ?? null;
    $parentId = $parent ? ($_GET[$parent] ?? '') : '';
    if ($parent && !ctype_digit($parentId)) {
        bad('Invalid parent record.');
    }

    $values = validate($page['fields'], $_POST);
    $id = $_POST['id'] ?? '';

    // ADD
    if ($id === '') {
        if ($parent) {
            $values[$parent] = $parentId; // link the new row to its parent
        }
        $columns = implode(', ', array_keys($values));
        $marks   = implode(', ', array_fill(0, count($values), '?'));
        q("INSERT INTO {$page['table']} ($columns) VALUES ($marks)", array_values($values));
        return 'Record added.';
    }

    // UPDATE
    if (!ctype_digit($id)) {
        bad('Invalid ID.');
    }
    $sets = [];
    foreach (array_keys($values) as $column) {
        $sets[] = "$column = ?";
    }
    $sql = "UPDATE {$page['table']} SET " . implode(', ', $sets) . " WHERE {$page['key']} = ?";
    $params = array_values($values);
    $params[] = $id;

    if ($parent) { // make sure the row really belongs to this parent
        $sql .= " AND $parent = ?";
        $params[] = $parentId;
    }
    if (q($sql, $params)->rowCount() === 0) {
        bad('Record not found.');
    }
    return 'Record updated.';
}

// Delete the row that was posted
function deleteRow($page) {
    $parent = $page['parent'] ?? null;
    $id = $_POST['delete'];
    if (!ctype_digit($id)) {
        bad('Invalid ID.');
    }

    $sql = "DELETE FROM {$page['table']} WHERE {$page['key']} = ?";
    $params = [$id];
    if ($parent) {
        $sql .= " AND $parent = ?";
        $params[] = $_GET[$parent] ?? 0;
    }
    if (q($sql, $params)->rowCount() === 0) {
        bad('Record not found.');
    }
    return 'Record deleted.';
}

// Save a borrowing, its items, and the stock deduction in ONE database transaction:
// either everything is saved, or nothing is.
function saveBorrowing() {
    global $db, $pages;

    // Validate the header (the status is left out, it uses the table default)
    $fields = $pages['transactions']['fields'];
    unset($fields['status']);
    $header = validate($fields, $_POST);

    // Collect the item rows from the form
    $items = [];
    foreach ($_POST['eq'] ?? [] as $i => $equipmentId) {
        $equipmentId = trim($equipmentId);
        $qty = trim($_POST['qty'][$i] ?? '');

        if ($equipmentId === '' && $qty === '') {
            continue; // empty row, skip it
        }
        $qtyOk = filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
        if (!ctype_digit($equipmentId) || !$qtyOk) {
            bad('Each item needs equipment and a quantity of at least 1.');
        }
        $items[] = [$equipmentId, $qty];
    }
    if (!$items) {
        bad('Add at least one equipment item.');
    }

    try {
        $db->beginTransaction();

        $id = q('INSERT INTO borrow_transactions (borrower_id, borrow_date, due_date)
                 VALUES (?, ?, ?) RETURNING borrow_id',
                [$header['borrower_id'], $header['borrow_date'], $header['due_date']])->fetchColumn();

        foreach ($items as [$equipmentId, $qty]) {
            q('INSERT INTO borrow_items (borrow_id, equipment_id, quantity) VALUES (?, ?, ?)',
              [$id, $equipmentId, $qty]);

            // Subtract the borrowed quantity from the stock.
            // The WHERE stops it from going below 0 (0 rows updated = not enough stock).
            $updated = q('UPDATE equipment SET quantity_available = quantity_available - ?
                          WHERE equipment_id = ? AND quantity_available >= ?',
                         [$qty, $equipmentId, $qty])->rowCount();
            if ($updated === 0) {
                bad('Not enough stock for one of the items. Nothing was saved.');
            }
        }

        // Demo checkbox: fail on purpose to show the rollback
        if (isset($_POST['fail'])) {
            throw new InvalidArgumentException('Simulated failure: everything was rolled back.');
        }

        $db->commit();
        return "Borrowing #$id saved.";
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e; // let handlePost() show the message
    }
}

// Handle any form submission, save a message, then redirect
// (redirecting stops the form from being re-sent when the page is refreshed)
function handlePost() {
    global $pages;

    $name = $_GET['p'] ?? '';
    $page = $pages[$name] ?? null;

    // Where to go back to afterwards
    $back = '?p=' . urlencode($name);
    if ($page && isset($page['parent'])) {
        $back .= '&' . $page['parent'] . '=' . urlencode($_GET[$page['parent']] ?? '');
    }

    try {
        if ($name === 'borrow') {
            $message = saveBorrowing();
        } elseif ($page && isset($_POST['delete'])) {
            $message = deleteRow($page);
        } elseif ($page) {
            $message = savePage($page);
        } else {
            bad('Unknown page.');
        }
        $_SESSION['msg'] = [true, $message];
    } catch (InvalidArgumentException $e) {
        $_SESSION['msg'] = [false, $e->getMessage()];
    } catch (PDOException $e) {
        $_SESSION['msg'] = [false, dbError($e)];
    }

    header("Location: $back");
    exit;
}
