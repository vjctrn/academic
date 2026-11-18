<?php
// Database settings
// $dbHost = '192.168.0.239';
$dbHost = '192.168.0.239';
$dbPort = '5432';
$dbName = 'borrow_db';
$dbUser = 'postgres';
$dbPass = '200924';

// Make text safe to print in HTML
function h($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES);
}

// Stop with an error message that the user will see
function bad($message)
{
    throw new InvalidArgumentException($message);
}

// Run a prepared SQL statement and return the result
function q($sql, $params = [])
{
    global $db;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Turn a database error into a message people can understand
function dbError(PDOException $e)
{
    $messages = [
        '23505' => 'Duplicate value: that number or name already exists.',
        '23503' => 'Invalid reference, or the record is still used by other records.',
        '23514' => 'Rule violated (quantity must be positive, due date cannot be before borrow date).',
        '23502' => 'A required field is missing.',
        '22P02' => 'Invalid value format.',
        '22007' => 'Invalid date.',
        '22008' => 'Invalid date.',
        '22001' => 'A value is too long.',
        '42P01' => 'Missing table or view: run pgadmin.sql in pgAdmin first.',
        '42883' => 'Missing function: run pgadmin.sql in pgAdmin first.',
    ];
    $code = (string)$e->getCode();
    $detail = $e->errorInfo[2] ?? $e->getMessage();

    // P0001 = message written by our own trigger/function, so show it as is
    if ($code === 'P0001') {
        return $detail;
    }
    return $messages[$code] ?? "Database error [$code]: $detail";
}

// Connect to PostgreSQL ($db is used everywhere else)
try {
    $db = new PDO(
        "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Cannot connect to the database. Check the settings in db.php.<br>' . h($e->getMessage()));
}
