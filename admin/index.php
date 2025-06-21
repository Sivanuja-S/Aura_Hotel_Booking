<?php
// admin/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start the session. This MUST be the very first line after <?php

// Check if the user is logged in AND is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php"); // Redirect to login page if not logged in or not admin
    exit;
}

require_once '../db_config.php'; // Adjust path as necessary if admin folder is nested

// Example: Fetch some summary data for the admin dashboard
$total_rooms = 0;
$total_bookings = 0;
$pending_bookings = 0;
$total_users = 0;

// Fetch total rooms
$sql_total_rooms = "SELECT COUNT(id) AS total FROM rooms";
$result_total_rooms = $conn->query($sql_total_rooms);
if ($result_total_rooms) {
    $total_rooms = $result_total_rooms->fetch_assoc()['total'];
}

// Fetch total bookings
$sql_total_bookings = "SELECT COUNT(id) AS total FROM bookings";
$result_total_bookings = $conn->query($sql_total_bookings);
if ($result_total_bookings) {
    $total_bookings = $result_total_bookings->fetch_assoc()['total'];
}

// Fetch pending bookings
$sql_pending_bookings = "SELECT COUNT(id) AS total FROM bookings WHERE status = 'pending'";
$result_pending_bookings = $conn->query($sql_pending_bookings);
if ($result_pending_bookings) {
    $pending_bookings = $result_pending_bookings->fetch_assoc()['total'];
}

// Fetch total users
$sql_total_users = "SELECT COUNT(id) AS total FROM users";
$result_total_users = $conn->query($sql_total_users);
if ($result_total_users) {
    $total_users = $result_total_users->fetch_assoc()['total'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Aura Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_style.css"> <style>
        body { background-color: #f4f7f6; }
        .admin-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .admin-card {
            background-color: #e9f0f4;
            border-left: 5px solid #0d6efd;
            border-radius: 8px;
        }
        .admin-card .card-body {
            padding: 20px;
        }
        .admin-card .card-title {
            color: #0d6efd;
            font-weight: bold;
        }
        .admin-card .card-text {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_rooms.php">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_bookings.php">Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="../dashboard.php">User View</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container admin-container">
        <h1 class="mb-4 text-center">Admin Dashboard</h1>
        <p class="text-center text-muted">Manage your hotel operations efficiently.</p>

        <div class="row mt-5">
            <div class="col-md-3 mb-4">
                <div class="card admin-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Rooms</h5>
                        <p class="card-text"><?php echo $total_rooms; ?></p>
                        <a href="manage_rooms.php" class="btn btn-sm btn-primary">Manage Rooms</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card admin-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Bookings</h5>
                        <p class="card-text"><?php echo $total_bookings; ?></p>
                        <a href="manage_bookings.php" class="btn btn-sm btn-primary">Manage Bookings</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card admin-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Pending Bookings</h5>
                        <p class="card-text"><?php echo $pending_bookings; ?></p>
                        <a href="manage_bookings.php?status=pending" class="btn btn-sm btn-warning">Review Pending</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card admin-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text"><?php echo $total_users; ?></p>
                        <a href="manage_users.php" class="btn btn-sm btn-primary">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 text-center">
            <h3>Quick Actions</h3>
            <a href="manage_rooms.php?action=add" class="btn btn-success btn-lg mx-2 my-2">Add New Room</a>
            <a href="manage_users.php?action=add" class="btn btn-info btn-lg mx-2 my-2">Add New User</a>
            </div>

        <div class="mt-4 text-center">
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>