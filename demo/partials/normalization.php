<?php
$joinSql = "SELECT s.name, s.email, c.course_name, c.instructor
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN courses c ON e.course_id = c.course_id";

echo "<h2>Normalization — " . ($mode === 'bad' ? '❌ Without It' : '✅ With It') . "</h2>";

if ($mode === 'bad') {
    echo "<h3>Current data</h3>";
    renderTable($pdo->query("SELECT * FROM enrollments_bad")->fetchAll());

    echo '<a class="btn" href="?concept=normalization&mode=bad&action=run">▶ Run: update Ana\'s email in one row only</a>';

    if ($action === 'run') {
        $pdo->exec("UPDATE enrollments_bad SET student_email='ana.newemail@mail.com' WHERE enrollment_id=1");
        echo "<h3>After running — Ana now has two different emails</h3>";
        renderTable($pdo->query("SELECT * FROM enrollments_bad")->fetchAll());
    }
} else {
    echo "<h3>Current data (via JOIN)</h3>";
    renderTable($pdo->query("SELECT s.name, s.email, c.course_name, c.instructor
                             FROM enrollments e
                             JOIN students s ON e.student_id = s.student_id
                             JOIN courses c ON e.course_id = c.course_id")->fetchAll());

    echo '<a class="btn" href="?concept=normalization&mode=good&action=run">▶ Run: update Ana\'s email once</a>';

    if ($action === 'run') {
        $pdo->exec("UPDATE students SET email='ana.newemail@mail.com' WHERE name='Ana Cruz'");
        echo "<h3>After running — every reference to Ana updates automatically</h3>";
        renderTable($pdo->query("SELECT s.name, s.email, c.course_name, c.instructor
                                 FROM enrollments e
                                 JOIN students s ON e.student_id = s.student_id
                                 JOIN courses c ON e.course_id = c.course_id")->fetchAll());
    }
}