<?php
require 'db.php';
$pdo = getConnection();

$pdo->exec("DELETE FROM enrollments_bad");
$pdo->exec("ALTER TABLE enrollments_bad AUTO_INCREMENT = 1");
$pdo->exec("INSERT INTO enrollments_bad (student_name, student_email, course_name, instructor) VALUES
    ('Ana Cruz', 'ana@mail.com', 'Databases', 'Ms. Lopez'),
    ('Ana Cruz', 'ana@mail.com', 'Web Dev 101', 'Mr. Reyes'),
    ('Ben Tan', 'ben@mail.com', 'UX Design', 'Ms. Santos'),
    ('Cara Lim', 'cara@mail.com', 'Cybersecurity', 'Mr. Dizon')");

$pdo->exec("UPDATE course_seats_bad SET seats_left = 5 WHERE course_name = 'Web Dev 101'");

$pdo->exec("DELETE FROM enrollments");
$pdo->exec("DELETE FROM students");
$pdo->exec("DELETE FROM courses");
$pdo->exec("ALTER TABLE students AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE courses AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE enrollments AUTO_INCREMENT = 1");

$pdo->exec("INSERT INTO students (name, email) VALUES ('Ana Cruz','ana@mail.com'),('Ben Tan','ben@mail.com'),('Cara Lim','cara@mail.com')");
$pdo->exec("INSERT INTO courses (course_name, instructor, seats_left) VALUES
    ('Databases','Ms. Lopez',10),('UX Design','Ms. Santos',10),('Cybersecurity','Mr. Dizon',8),('Web Dev 101','Mr. Reyes',5)");
$pdo->exec("INSERT INTO enrollments (student_id, course_id) VALUES (1,1),(1,4),(2,2),(3,3)");

$concept = $_GET['concept'] ?? 'normalization';
$mode = $_GET['mode'] ?? 'bad';
header("Location: index.php?concept=$concept&mode=$mode");
exit;