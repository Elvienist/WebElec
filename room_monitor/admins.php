<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        $error = create_admin($username, $password);
        if ($error === null) {
            flash_set('success', 'Admin added.');
        } else {
            flash_set('error', $error);
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash_set('error', 'Something went wrong. Please try again.');
    }

    header('Location: admins.php');
    exit;
}

$page_title = 'Admins';
$active_tab = 'admins';

$admins = [];
$db_error = false;
try {
    $admins = db()->query(
        'SELECT a.username, c.username AS creator, a.created_at
         FROM admins a LEFT JOIN admins c ON c.id = a.created_by
         ORDER BY a.id ASC'
    )->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $db_error = true;
}

require __DIR__ . '/partials/header.php';
?>
<section class="card">
    <h2>Admins</h2>
    <?php if ($db_error): ?>
        <p class="empty">Cannot load admins. Check that MySQL is running.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table id="admin-list" class="table">
                <thead><tr><th>Username</th><th>Created by</th><th>Created at</th></tr></thead>
                <tbody>
                <?php foreach ($admins as $a): ?>
                    <tr>
                        <td><?= h($a['username']) ?></td>
                        <td><?= h($a['creator'] ?? '-') ?></td>
                        <td><?= h($a['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Add admin</h2>
    <form id="form-admin" method="post" action="admins.php">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <div class="form-row">
            <label>Username
                <input type="text" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]+" autocomplete="off">
            </label>
            <label>Password
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
            </label>
            <button class="btn btn-neutral" type="submit">Add admin</button>
        </div>
        <p class="hint">Username: 3 to 50 letters, digits, _ . -. Password: at least 8 characters.</p>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>