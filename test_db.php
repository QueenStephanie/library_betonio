<?php
require_once __DIR__ . '/includes/config.php';
$stmt = $db->query("SELECT id, title FROM books ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
