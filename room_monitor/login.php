<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Already logged in: go to the dashboard.
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if ($username !== '' && $password !== '' && attempt_login($username, $password)) {
            header('Location: index.php');
            exit;
        }
        flash_set('error', 'Invalid username or password.');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash_set('error', 'Cannot reach the database. Check that MySQL is running and the schema is imported.');
    }

    header('Location: login.php');
    exit;
}

$page_title = 'Login';
$active_tab = '';
require __DIR__ . '/partials/header.php';
?>
<section class="card card-narrow">
    <h2>Admin login</h2>
    <form id="form-login" method="post" action="login.php">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <label>Username
            <input type="text" name="username" required maxlength="50" autocomplete="username" autofocus>
        </label>

        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>

        <button class="btn btn-neutral" type="submit">Log in</button>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>