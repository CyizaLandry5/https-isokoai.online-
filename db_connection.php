<?php
$host = "localhost";
$user = "u515424954_isokoai";
$pass = "Rwema123!";
$dbname = "u515424954_isokoai";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>