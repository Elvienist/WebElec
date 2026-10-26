<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$page_title = $page_title ?? 'Room Monitor';
$active_tab = $active_tab ?? '';
$logged_in  = !empty($_SESSION['admin_id']);
$flash      = flash_get();

$css_version = @filemtime(__DIR__ . '/../assets/style.css') ?: 1;
$js_version  = @filemtime(__DIR__ . '/../assets/app.js') ?: 1;

$tabs = [
    'dashboard' => ['index.php', 'Dashboard'],
    'records'   => ['records.php', 'Records'],
    'admins'    => ['admins.php', 'Admins'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($page_title) ?> · Room Monitor</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= (int) $css_version ?>">
    <script src="assets/app.js?v=<?= (int) $js_version ?>" defer></script>
</head>
<body>
<canvas id="bg-canvas" aria-hidden="true"></canvas>

<header class="topbar">
    <h1 class="brand">Room Monitor</h1>
    <?php if ($logged_in): ?>
        <nav class="tabs">
            <?php foreach ($tabs as $key => [$href, $text]): ?>
                <a class="tab<?= $active_tab === $key ? ' active' : '' ?>" href="<?= h($href) ?>"><?= h($text) ?></a>
            <?php endforeach; ?>
            <a class="tab tab-logout" href="logout.php">Logout</a>
        </nav>
        <span class="whoami"><?= h($_SESSION['admin_username'] ?? '') ?></span>
    <?php endif; ?>
</header>

<main class="page">
<?php if ($flash): ?>
    <div class="message message-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
        <?= h($flash['message']) ?>
    </div>
<?php endif; ?>