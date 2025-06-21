<?php
// db_config.php

$servername = "localhost"; 
$username = "root";      
$password = "";          
$dbname = "aura_hotel";  

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully"; 
?>