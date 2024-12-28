<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    http_response_code(403);
    exit('Unauthorized');
}

if (!isset($_POST['post_id'])) {
    http_response_code(400);
    exit('Missing post ID');
}

$post_id = $_POST['post_id'];
$student_id = $_SESSION['user_id'];

// Check if already accepted
$check_query = "SELECT id FROM user_jobs WHERE post_id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    http_response_code(400);
    exit('Already accepted this work');
}

// Insert new acceptance
$insert_query = "INSERT INTO user_jobs (post_id, student_id, status, accepted_at) VALUES (?, ?, 'accepted', NOW())";
$stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $student_id);

if (mysqli_stmt_execute($stmt)) {
    echo 'Success';
} else {
    http_response_code(500);
    echo 'Error accepting work';
} 