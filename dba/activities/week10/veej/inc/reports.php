<?php
// Return a date from the URL only if it looks like YYYY-MM-DD, otherwise ''
function readDate($name)
{
    $value = $_GET[$name] ?? '';
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

// Reports page with two tabs. Each report is [title, SQL, params].
function showReports()
{
    global $statuses;

    $tabs = ['w6' => 'Week 6', 'w7' => 'Week 7'];
    $tab = $_GET['tab'] ?? 'w6';
    if (!isset($tabs[$tab])) {
        $tab = 'w6';
    }

    echo '<h2>Reports</h2><div class="tabs">';
    foreach ($tabs as $key => $label) {
        echo '<a href="?p=reports&tab=' . $key . '"' . ($key === $tab ? ' class="on"' : '') . '>' . $label . '</a>';
    }
    echo '</div>';

    $reports = ($tab === 'w6') ? week6Reports($statuses) : week7Reports();

    // Run each report. If one fails, show its error and keep going.
    foreach ($reports as [$title, $sql, $params]) {
        echo '<h3>' . h($title) . '</h3>';
        try {
            $rows = q($sql, $params)->fetchAll();
            echo makeTable($rows) . '<small>' . count($rows) . ' row(s)</small>';
        } catch (PDOException $e) {
            echo '<p class="err">' . h(dbError($e)) . '</p>';
        }
    }
}

// Week 6: filter form + listings and summaries
function week6Reports($statuses)
{
    // Read the filters from the URL
    $status = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
    $from = readDate('from');
    $to = readDate('to');
    $search = substr(trim($_GET['q'] ?? ''), 0, 50);

    // Filter form
    echo '<form method="get"><input type="hidden" name="p" value="reports"><input type="hidden" name="tab" value="w6">';
    echo 'Status <select name="status"><option value="">Any</option>';
    foreach ($statuses as $s) {
        echo '<option' . ($s === $status ? ' selected' : '') . '>' . $s . '</option>';
    }
    echo '</select> From <input type="date" name="from" value="' . h($from) . '"> ';
    echo 'To <input type="date" name="to" value="' . h($to) . '"> ';
    echo 'Search text <input name="q" value="' . h($search) . '" maxlength="50"> <button>Apply</button></form>';

    // Build the WHERE part of the borrowing records report from the filters
    $where = ['b.full_name ILIKE ?'];
    $params = ['%' . $search . '%'];
    if ($status !== '') {
        $where[] = 't.status = ?';
        $params[] = $status;
    }
    if ($from !== '') {
        $where[] = 't.borrow_date >= ?';
        $params[] = $from;
    }
    if ($to !== '') {
        $where[] = 't.borrow_date <= ?';
        $params[] = $to;
    }

    $likeSearch = '%' . $search . '%';

    return [
        [
            'Equipment Inventory Listing (search + sorting)',
            'SELECT equipment_name AS "Equipment", property_no AS "Property No", quantity_available AS "Available Qty"
             FROM equipment
             WHERE equipment_name ILIKE ? OR property_no ILIKE ?
             ORDER BY quantity_available DESC, equipment_name ASC',
            [$likeSearch, $likeSearch]
        ],

        [
            'Filtered Borrowing Records (status, date range, borrower search)',
            'SELECT t.borrow_id AS "Txn #", b.full_name AS "Borrower", t.borrow_date AS "Borrowed",
                    t.due_date AS "Due", t.status AS "Status"
             FROM borrow_transactions t
             JOIN borrowers b ON b.borrower_id = t.borrower_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY t.borrow_date DESC, t.borrow_id',
            $params
        ],

        [
            'Borrower Type Summary (DISTINCT + window COUNT)',
            'SELECT DISTINCT borrower_type AS "Borrower Type",
                    COUNT(*) OVER (PARTITION BY borrower_type) AS "Borrowers"
             FROM borrowers ORDER BY 2 DESC, 1',
            []
        ],

        [
            'Transaction Status Summary (shows every status, even with 0)',
            "SELECT s.status AS \"Status\", COUNT(t.borrow_id) AS \"Transactions\"
             FROM (VALUES ('Borrowed'), ('Returned'), ('Overdue'), ('Cancelled')) s(status)
             LEFT JOIN borrow_transactions t ON t.status = s.status
             GROUP BY s.status ORDER BY 2 DESC, 1",
            []
        ],

        [
            'Grouped Borrowing Analysis (GROUP BY + HAVING: types with 2+ transactions)',
            "SELECT b.borrower_type AS \"Borrower Type\", COUNT(*) AS \"Transactions\"
             FROM borrow_transactions t
             JOIN borrowers b ON b.borrower_id = t.borrower_id
             WHERE t.status IN ('Borrowed', 'Overdue', 'Returned')
             GROUP BY b.borrower_type HAVING COUNT(*) >= 2",
            []
        ],

        [
            'Stock Analysis from vw_equipment_usage (ROUND, ABS, NULLIF, CASE)',
            'SELECT DISTINCT equipment_name AS "Equipment", quantity_available AS "Stock",
                    total_quantity_borrowed AS "Ever Borrowed",
                    ROUND(100.0 * total_quantity_borrowed / NULLIF(quantity_available, 0), 1) AS "% of Stock Borrowed",
                    ABS(quantity_available - 5) AS "Distance from Reorder Level (5)",
                    availability AS "Availability (from view)",
                    CASE WHEN quantity_available = 0 THEN \'Out of Stock\'
                         WHEN total_quantity_borrowed = 0 THEN \'Never borrowed\'
                         WHEN 100.0 * total_quantity_borrowed / quantity_available >= 50 THEN \'High demand\'
                         ELSE \'Normal demand\' END AS "Demand"
             FROM vw_equipment_usage
             ORDER BY 4 DESC NULLS LAST, 1',
            []
        ],

        [
            'Borrowing Standing from vw_borrowing_details (CURRENT_DATE + CASE)',
            'SELECT DISTINCT v.borrow_id AS "Txn #", v.full_name AS "Borrower", v.status AS "Recorded Status",
                    v.due_date AS "Due", (v.due_date - v.borrow_date) AS "Loan Days",
                    CASE WHEN v.status IN (\'Returned\', \'Cancelled\') THEN v.status
                         WHEN v.due_date < CURRENT_DATE THEN \'Overdue by \' || (CURRENT_DATE - v.due_date) || \' day(s)\'
                         ELSE \'On time\' END AS "Actual Standing"
             FROM vw_borrowing_details v
             ORDER BY 1',
            []
        ],
    ];
}

// Week 7: joins, aggregates, UNION, and function/trigger reports
function week7Reports()
{
    $qty = max(1, (int)($_GET['rq'] ?? 2)); // quantity to test for availability

    echo '<form method="get"><input type="hidden" name="p" value="reports"><input type="hidden" name="tab" value="w7">';
    echo 'Requested quantity <input type="number" name="rq" min="1" value="' . $qty . '" style="width:5em"> <button>Apply</button></form>';

    return [
        [
            'Complete Borrowing Report (4-table JOIN + string and date functions)',
            "SELECT b.borrower_no AS \"Borrower No\",
                    CONCAT_WS(' - ', UPPER(b.full_name), INITCAP(b.borrower_type)) AS \"Borrower\",
                    TRIM(e.equipment_name) AS \"Equipment\", bi.quantity AS \"Qty\",
                    TO_CHAR(t.borrow_date, 'Mon DD, YYYY') AS \"Borrowed\",
                    TO_CHAR(t.due_date, 'Mon DD, YYYY') AS \"Due\", t.status AS \"Status\"
             FROM borrowers b
             JOIN borrow_transactions t ON t.borrower_id = b.borrower_id
             JOIN borrow_items bi ON bi.borrow_id = t.borrow_id
             JOIN equipment e ON e.equipment_id = bi.equipment_id
             ORDER BY t.borrow_date, b.borrower_no, e.equipment_name",
            []
        ],

        [
            'Equipment Borrowing Summary (aggregates + LEFT JOIN, unborrowed equipment included)',
            "SELECT TRIM(e.equipment_name) AS \"Equipment\", e.property_no AS \"Property No\",
                    COUNT(DISTINCT bi.borrow_id) AS \"Transactions\",
                    COALESCE(SUM(bi.quantity), 0) AS \"Total Qty\",
                    ROUND(AVG(bi.quantity), 2) AS \"Avg Qty\", MIN(bi.quantity) AS \"Min Qty\", MAX(bi.quantity) AS \"Max Qty\",
                    CASE WHEN COUNT(bi.borrow_item_id) = 0 THEN 'Never borrowed' ELSE 'Borrowed before' END AS \"History\"
             FROM equipment e
             LEFT JOIN borrow_items bi ON bi.equipment_id = e.equipment_id
             GROUP BY e.equipment_id, e.equipment_name, e.property_no
             ORDER BY 4 DESC, 1",
            []
        ],

        [
            'Borrower Activity (LEFT JOIN, borrowers with no transactions included)',
            "SELECT b.borrower_no AS \"Borrower No\", INITCAP(TRIM(b.full_name)) AS \"Name\",
                    COUNT(t.borrow_id) AS \"Transactions\",
                    COALESCE(TO_CHAR(MAX(t.borrow_date), 'Mon DD, YYYY'), 'No borrowing history') AS \"Last Borrowed\"
             FROM borrowers b
             LEFT JOIN borrow_transactions t ON t.borrower_id = b.borrower_id
             GROUP BY b.borrower_id, b.borrower_no, b.full_name
             ORDER BY 3, 1",
            []
        ],

        // set_a = Borrowed/Overdue borrowers, set_b = Overdue/Returned borrowers
        // UNION removes the duplicate name, UNION ALL keeps it
        [
            'UNION vs UNION ALL',
            "WITH set_a AS (
                 SELECT br.full_name AS borrower FROM borrowers br
                 JOIN borrow_transactions t ON t.borrower_id = br.borrower_id
                 WHERE t.status IN ('Borrowed', 'Overdue')),
             set_b AS (
                 SELECT br.full_name AS borrower FROM borrowers br
                 JOIN borrow_transactions t ON t.borrower_id = br.borrower_id
                 WHERE t.status IN ('Overdue', 'Returned'))
             SELECT 'UNION' AS \"Operation\", COUNT(*) AS \"Rows\",
                    STRING_AGG(borrower, ', ' ORDER BY borrower) AS \"Borrowers\"
             FROM (SELECT * FROM set_a UNION SELECT * FROM set_b) u
             UNION ALL
             SELECT 'UNION ALL', COUNT(*), STRING_AGG(borrower, ', ' ORDER BY borrower)
             FROM (SELECT * FROM set_a UNION ALL SELECT * FROM set_b) u",
            []
        ],

        [
            'Monthly Borrowing Report (DATE_TRUNC, EXTRACT, TO_CHAR)',
            "SELECT TO_CHAR(DATE_TRUNC('month', t.borrow_date), 'FMMonth YYYY') AS \"Month\",
                    EXTRACT(YEAR FROM t.borrow_date)::int AS \"Year\",
                    COUNT(DISTINCT t.borrow_id) AS \"Transactions\",
                    COALESCE(SUM(bi.quantity), 0) AS \"Total Qty\",
                    ROUND(AVG(bi.quantity), 2) AS \"Avg Qty/Item\", MIN(bi.quantity) AS \"Min\", MAX(bi.quantity) AS \"Max\"
             FROM borrow_transactions t
             LEFT JOIN borrow_items bi ON bi.borrow_id = t.borrow_id
             GROUP BY DATE_TRUNC('month', t.borrow_date), EXTRACT(YEAR FROM t.borrow_date)
             ORDER BY DATE_TRUNC('month', t.borrow_date)",
            []
        ],

        [
            'Database functions: fn_total_borrowed() and fn_check_equipment_availability()',
            'SELECT equipment_name AS "Equipment", quantity_available AS "Stock",
                    fn_total_borrowed(equipment_id) AS "Currently Borrowed",
                    fn_check_equipment_availability(equipment_id, ?::int) AS "Can Borrow Requested Qty?"
             FROM equipment ORDER BY equipment_id',
            [$qty]
        ],

        [
            'borrow_items_audit (filled by a trigger, latest 20. Test it from Transactions > Items)',
            'SELECT * FROM borrow_items_audit ORDER BY audit_id DESC LIMIT 20',
            []
        ],
    ];
}
