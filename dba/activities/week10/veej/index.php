<?php
session_start(); // needed for the flash messages

require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/actions.php';
require __DIR__ . '/inc/views.php';
require __DIR__ . '/inc/reports.php';

// Form submissions are handled first (they redirect when done)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePost();
}

$page = $_GET['p'] ?? 'borrowers';

$menu = [
    'borrowers'    => 'Borrowers',
    'equipment'    => 'Equipment',
    'transactions' => 'Transactions',
    'borrow'       => 'New Borrowing',
    'reports'      => 'Reports',
];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipment Borrowing</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <nav>
        <?php
        foreach ($menu as $key => $label) {
            // The Items page belongs to Transactions, so highlight that tab for it
            $active = ($key === $page) || ($page === 'items' && $key === 'transactions');
            echo '<a href="?p=' . $key . '"' . ($active ? ' class="on"' : '') . '>' . $label . '</a>';
        }
        ?>
    </nav>
    <main>
        <?php
        // Show the message saved by handlePost(), then remove it
        if (isset($_SESSION['msg'])) {
            [$ok, $text] = $_SESSION['msg'];
            unset($_SESSION['msg']);
            echo '<p class="' . ($ok ? 'ok' : 'err') . '">' . h($text) . '</p>';
        }

        // Show the page that was asked for
        try {
            if (isset($pages[$page])) {
                showCrudPage($pages[$page], $page);
            } elseif ($page === 'borrow') {
                showBorrowForm();
            } elseif ($page === 'reports') {
                showReports();
            } else {
                echo '<p class="err">Page not found.</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="err">' . h(dbError($e)) . '</p>';
        }
        ?>
    </main>
</body>

</html>