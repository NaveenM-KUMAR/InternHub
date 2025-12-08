<?php
$host = "localhost";
$user = "root";
$password = ""; 
$database = "users_db";
// The fifth argument is for the port number
$conn = new mysqli($host, $user, $password, $database,); 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// ... rest of the code
?>