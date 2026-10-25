<?php
$host = 'localhost';
$dbname = 'demo_db';
$user = 'root';
$pass = 'YOUR_MYSQL_PASSWORD_HERE';

function getConnection() {
    global $host, $dbname, $user, $pass;
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function renderTable($rows) {
    if (empty($rows)) {
        echo "<p><em>No rows.</em></p>";
        return;
    }
    echo "<table class='data-table'><thead><tr>";
    foreach (array_keys($rows[0]) as $col) {
        if (!is_int($col)) echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr></thead><tbody>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $key => $val) {
            if (!is_int($key)) echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";
}