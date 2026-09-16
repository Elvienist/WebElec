<?php
echo "<h2>Prepared Statements — " . ($mode === 'bad' ? '❌ Without Them' : '✅ With Them') . "</h2>";

$userInput = "' OR '1'='1";

if ($mode === 'bad') {
    echo "<h3>Current data</h3>";
    renderTable($pdo->query("SELECT * FROM enrollments_bad")->fetchAll());

    echo "<p>Simulated malicious input: <code>" . htmlspecialchars($userInput) . "</code></p>";
    echo '<a class="btn" href="?concept=prepared&mode=bad&action=run">▶ Run: search using raw string concatenation</a>';

    if ($action === 'run') {
        $query = "SELECT * FROM enrollments_bad WHERE student_name = '$userInput'";
        echo "<p>Query actually sent to MySQL:</p><code>" . htmlspecialchars($query) . "</code>";

        echo "<h3>Result — returns ALL rows, even though no student has that name</h3>";
        renderTable($pdo->query($query)->fetchAll());
    }
} else {
    echo "<h3>Current data</h3>";
    renderTable($pdo->query("SELECT * FROM students")->fetchAll());

    echo "<p>Same malicious input: <code>" . htmlspecialchars($userInput) . "</code></p>";
    echo '<a class="btn" href="?concept=prepared&mode=good&action=run">▶ Run: search using a prepared statement</a>';

    if ($action === 'run') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE name = ?");
        $stmt->execute([$userInput]);
        $results = $stmt->fetchAll();

        echo "<h3>Result — input bound as data, never as SQL</h3>";
        renderTable($results);
    }
}