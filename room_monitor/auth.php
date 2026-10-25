<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function attempt_login(string $username, string $password): bool {
    $stmt = db()->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        return true;
    }
    return false;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function create_admin(string $username, string $password): ?string {
    if (!preg_init('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) { // Note: use preg_match
        // Will adjust below
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        return "Username must be 3 to 50 characters of letters, digits, _, ., -.";
    }
    if (mb_strlen($password) < 8) {
        return "Password must be at least 8 characters.";
    }

    $stmt = db()->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return "Username already exists.";
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $created_by = $_SESSION['admin_username'] ?? 'system';

    $stmt = db()->prepare("INSERT INTO admins (username, password_hash, created_by, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $hash, $created_by, date('Y-m-d H:i:s')]);

    return null;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): void {
    $token = $_POST['csrf'] ?? '';
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit("Invalid CSRF token.");
    }
}