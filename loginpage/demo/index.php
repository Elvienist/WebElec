<?php
require 'db.php';
$pdo = getConnection();

$concept = $_GET['concept'] ?? 'normalization';
$mode    = $_GET['mode'] ?? 'bad';
$action  = $_GET['action'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
<title>DB Concepts Demo</title>
<style>
    .btn { display:inline-block; padding:8px 14px; margin:4px; text-decoration:none;
         border:1px solid #999; border-radius:4px; color:#000; }
    .active { font-weight:bold; background:#333; color:#fff; }
    .bad { background:#fdd; padding:16px; }
    .good { background:#dfd; padding:16px; }
    .data-table { border-collapse: collapse; margin: 8px 0; width: 100%; }
    .data-table th, .data-table td { border: 1px solid #999; padding: 6px 10px; text-align: left; }
    .data-table th { background: #333; color: #fff; }
    .data-table tr:nth-child(even) { background: #f2f2f2; } 
</style>
</head>
<body>

<h1>PHP/MySQL Concept Demos</h1>

<nav>
  <a class="btn <?= $concept==='normalization'?'active':'' ?>" href="?concept=normalization&mode=<?= $mode ?>">Normalization</a>
  <a class="btn <?= $concept==='prepared'?'active':'' ?>" href="?concept=prepared&mode=<?= $mode ?>">Prepared Statements</a>
  <a class="btn <?= $concept==='transactions'?'active':'' ?>" href="?concept=transactions&mode=<?= $mode ?>">Transactions</a>
</nav>

<nav>
  <a class="btn <?= $mode==='bad'?'active':'' ?>" href="?concept=<?= $concept ?>&mode=bad">❌ Bad</a>
  <a class="btn <?= $mode==='good'?'active':'' ?>" href="?concept=<?= $concept ?>&mode=good">✅ Good</a>
  <a class="btn" href="reset.php?concept=<?= $concept ?>&mode=<?= $mode ?>">🔄 Reset Data</a>
</nav>

<hr>

<div class="<?= $mode ?>">
<?php
$file = __DIR__ . "/partials/$concept.php";
if (file_exists($file)) {
    include $file; // uses $pdo, $mode, $action
} else {
    echo "Unknown concept.";
}
?>
</div>

</body>
</html>