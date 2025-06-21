<?php
// admin/includes/admin_header.php
session_start(); // Always start the session at the very beginning

// Basic Admin Authentication Check
// If 'is_admin' session variable is not set OR it's not true, redirect to login page.
// This ensures only authenticated admins can access subsequent pages.
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("location: ../login.php"); // Redirect to login page (assuming login.php is in parent directory)
    exit; // Stop script execution to prevent further output
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> | Aura Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6; /* Light gray background */
        }
        #wrapper {
            display: flex; /* Use flexbox for sidebar and content layout */
        }
        .admin-sidebar {
            background-color: #343a40; /* Dark gray sidebar background */
            color: #fff; /* White text for sidebar */
            min-height: 100vh; /* Full height sidebar */
            padding-top: 20px;
            width: 250px; /* Fixed width for sidebar */
            flex-shrink: 0; /* Prevent sidebar from shrinking */
        }
        .admin-sidebar .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
            border-bottom: 1px solid #474b4f; /* Separator for heading */
            margin-bottom: 20px;
        }
        .admin-sidebar .list-group-item {
            border: none; /* Remove borders between list items */
            padding: 10px 15px;
            color: #adb5bd; /* Light gray text for links */
            background-color: transparent; /* Transparent background */
            transition: all 0.3s ease; /* Smooth transition for hover effects */
        }
        .admin-sidebar .list-group-item:hover, .admin-sidebar .list-group-item.active {
            color: #fff; /* White text on hover/active */
            background-color: #007bff; /* Blue background on hover/active */
            border-radius: 5px; /* Slightly rounded corners */
        }
        .content-area {
            flex-grow: 1; /* Content area takes up remaining space */
            padding: 30px;
            overflow-x: auto; /* Allow horizontal scrolling for tables */
        }
        .navbar-light {
            border-bottom: 1px solid #dee2e6; /* Border below navbar */
        }

        /* Responsive adjustments for sidebar */
        @media (max-width: 768px) {
            #wrapper.toggled .admin-sidebar {
                margin-left: 0;
            }
            .admin-sidebar {
                margin-left: -250px; /* Hide sidebar by default on small screens */
                position: fixed; /* Make sidebar fixed */
                z-index: 1000; /* Ensure sidebar is above content */
            }
            #page-content-wrapper {
                min-width: 100vw;
            }
        }
        #wrapper.toggled .admin-sidebar {
            margin-left: 0;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="bg-dark border-right admin-sidebar">
            <div class="sidebar-heading text-white text-center py-4">Aura Admin Panel</div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="rooms.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'rooms.php' || basename($_SERVER['PHP_SELF']) == 'add_room.php' || basename($_SERVER['PHP_SELF']) == 'edit_room.php') ? 'active' : ''; ?>">Room Management</a>
                <a href="bookings.php" class="list-group-item list-group-item-action bg-dark text-white disabled" aria-disabled="true">Bookings</a>
                <a href="users.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'add_user.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php') ? 'active' : ''; ?>">Users</a>                <a href="../logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary d-block d-md-none" id="sidebarToggle">Toggle Menu</button>
                    <div class="ms-auto">
                        <span class="navbar-text me-3">
                            Welcome, Admin!
                        </span>
                    </div>
                </div>
            </nav>
            <div class="container-fluid content-area">