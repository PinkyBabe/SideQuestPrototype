<?php
$servername = "localhost";
$username = "root";  // your database username
$password = "";      // your database password
$dbname = "sidequest_db";  // your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to handle special characters
mysqli_set_charset($conn, "utf8mb4");
?> 