<?php
$conn = new mysqli("localhost", "root", "", "concert_ticketing");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>