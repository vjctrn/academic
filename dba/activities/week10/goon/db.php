<?php
// $host = "192.168.18.3"; // change ts
$host = "172.22.172.94"; // change ts
$db = "db_borrow_thingie";
$user = "postgres";
$pass = "opop";

try {
  $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die("holee skill ishu: " . htmlspecialchars($e->getMessage()));
}
