<?php
if (php_sapi_name() !== 'cli') {
    echo "Error: This script can only be run from the command line.\n";
    exit(1);
}

require __DIR__ . '/../config.php';

try {
    $dsn = "mysql:host=127.0.0.1;port=3307;dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Set session time zone
    $pdo->exec("SET time_zone = '+08:00'");

    // Initialize system_state row
    $pdo->exec("INSERT IGNORE INTO system_state (id) VALUES (1)");
    echo "System state row initialized (or already exists).\n";

    // Create the first admin if the table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $adminCount = $stmt->fetchColumn();

    if ($adminCount == 0) {
        $username = $argv[1] ?? 'admin';
        $password = $argv[2] ?? 'ChangeMe123!';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');

        $insertAdmin = $pdo->prepare("INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)");
        $insertAdmin->execute([$username, $hash, $createdAt]);
        
        echo "First admin created (Username: {$username}).\n";
    } else {
        echo "Admins table is not empty. Skipping admin creation.\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}