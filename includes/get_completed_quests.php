<?php
require_once 'config.php';
require_once 'functions.php';

session_start();
$student_id = $_SESSION['user_id'];

$conn = Database::getInstance();
$query = "SELECT q.*, u.first_name, u.last_name, u.email 
          FROM quests q 
          JOIN users u ON q.faculty_id = u.id
          WHERE q.student_id = ? AND q.status = 'completed'
          ORDER BY q.completion_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

$quests = [];
while ($row = $result->fetch_assoc()) {
    $row['faculty_name'] = $row['first_name'] . ' ' . $row['last_name'];
    unset($row['first_name'], $row['last_name']);
    $quests[] = $row;
}

header('Content-Type: application/json');
echo json_encode($quests); 