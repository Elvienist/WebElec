<?php
echo "<h2>Transactions — " . ($mode === 'bad' ? '❌ Without Them' : '✅ With Them') . "</h2>";

if ($mode === 'bad') {
    echo "<h3>Seats left before enrolling:</h3>";
    renderTable($pdo->query("SELECT * FROM course_seats_bad WHERE course_name = 'Web Dev 101'")->fetchAll());

    echo '<a class="btn" href="?concept=transactions&mode=bad&action=run">▶ Run: enroll student, then simulate a crash</a>';

    if ($action === 'run') {
        $pdo->exec("INSERT INTO enrollments_bad (student_name, student_email, course_name, instructor)
                    VALUES ('Dan Reyes', 'dan@mail.com', 'Web Dev 101', 'Mr. Reyes')");
        echo "<p>✅ Enrollment row inserted.</p>";
        echo "<p>⚠️ Simulating a crash/error before the seat count updates...</p>";

        echo "<h3>Enrollments for Web Dev 101 (after):</h3>";
        renderTable($pdo->query("SELECT * FROM enrollments_bad WHERE course_name = 'Web Dev 101'")->fetchAll());

        echo "<h3>Seats left (never decremented!):</h3>";
        renderTable($pdo->query("SELECT * FROM course_seats_bad WHERE course_name = 'Web Dev 101'")->fetchAll());

        echo "<p><strong>🔴 Inconsistent: a new student is enrolled, but the seat count still shows the old number.</strong></p>";
    }
} else {
    echo "<h3>Seats left before enrolling:</h3>";
    renderTable($pdo->query("SELECT course_name, seats_left FROM courses WHERE course_name = 'Web Dev 101'")->fetchAll());

    echo '<a class="btn" href="?concept=transactions&mode=good&action=run">▶ Run: enroll student inside a transaction, then simulate a crash</a>';

    if ($action === 'run') {
        try {
            $pdo->beginTransaction();

            $pdo->exec("INSERT INTO students (name, email) VALUES ('Dan Reyes', 'dan@mail.com')");
            $studentId = $pdo->lastInsertId();
            $courseId = $pdo->query("SELECT course_id FROM courses WHERE course_name = 'Web Dev 101'")->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$studentId, $courseId]);
            echo "<p>✅ Step 1: Enrollment inserted.</p>";

            echo "<p>⚠️ Simulating a crash/error before the seat count updates...</p>";
            throw new Exception("Simulated failure — e.g. lost connection, server crash");

            $stmt = $pdo->prepare("UPDATE courses SET seats_left = seats_left - 1 WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color:red'>🔴 Error caught: " . htmlspecialchars($e->getMessage()) . " — rolling back ALL changes.</p>";
        }

        echo "<h3>Students named Dan Reyes (should be NONE — rollback undid the insert):</h3>";
        renderTable($pdo->query("SELECT * FROM students WHERE name = 'Dan Reyes'")->fetchAll());

        echo "<h3>Seats left (unchanged, as it should be):</h3>";
        renderTable($pdo->query("SELECT course_name, seats_left FROM courses WHERE course_name = 'Web Dev 101'")->fetchAll());

        echo "<p><strong>✅ Consistent: since the transaction rolled back, NEITHER the enrollment nor the seat count changed.</strong></p>";
    }
}