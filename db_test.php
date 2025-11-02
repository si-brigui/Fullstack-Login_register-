<?php
require __DIR__ . '/config.php';
echo "DB OK<br>";
$q = $conn->query("SELECT DATABASE() AS db");
$row = $q->fetch_assoc();
echo "Connected to DB: " . htmlspecialchars($row['db']);
