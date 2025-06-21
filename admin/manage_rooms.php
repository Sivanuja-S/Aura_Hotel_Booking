<?php
// admin/manage_rooms.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php"); // Redirect to login if not logged in or not admin
    exit;
}

require_once '../db_config.php'; // Correct path to db_config.php

$message = ""; // For success/error messages

// --- Handle Add/Edit Room ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $room_id = $_POST['room_id'] ?? null;
    $room_number = trim($_POST['room_number']);
    $room_type = trim($_POST['room_type']);
    $price_per_night = trim($_POST['price_per_night']);
    $capacity_adults = trim($_POST['capacity_adults']);
    $capacity_children = trim($_POST['capacity_children']);
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);

    if (empty($room_number) || empty($room_type) || empty($price_per_night) || empty($capacity_adults) || empty($capacity_children)) {
        $message = "<div class='alert alert-danger'>Please fill all required fields.</div>";
    } else {
        if ($action == 'add') {
            $sql = "INSERT INTO rooms (room_number, room_type, price_per_night, capacity_adults, capacity_children, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssddiss", $room_number, $room_type, $price_per_night, $capacity_adults, $capacity_children, $description, $image_url);
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Room added successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Error adding room: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>";
            }
        } elseif ($action == 'edit' && $room_id) {
            $sql = "UPDATE rooms SET room_number = ?, room_type = ?, price_per_night = ?, capacity_adults = ?, capacity_children = ?, description = ?, image_url = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssddissi", $room_number, $room_type, $price_per_night, $capacity_adults, $capacity_children, $description, $image_url, $room_id);
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Room updated successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Error updating room: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>";
            }
        }
    }
}

// --- Handle Delete Room ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM rooms WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Room deleted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error deleting room: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>";
    }
}

// --- Fetch Rooms for Display ---
$rooms = [];
$sql_fetch = "SELECT id, room_number, room_type, price_per_night, capacity_adults, capacity_children, description, image_url FROM rooms ORDER BY room_number ASC";
if ($result = $conn->query($sql_fetch)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    $result->free();
} else {
    $message = "<div class='alert alert-danger'>Error fetching rooms: " . $conn->error . "</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .wrapper { display: flex; }
        .sidebar { width: 250px; background-color: #343a40; color: white; min-height: 100vh; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover { background-color: #007bff; }
        .content { flex-grow: 1; padding: 30px; }
        .navbar-brand { color: white; font-weight: bold; padding-left: 20px; margin-bottom: 20px; display: block; }
        .form-section { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .table-section { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <span class="navbar-brand">Aura Admin</span>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_rooms.php">Rooms</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                <li class="nav-item"><a class="nav-link" href="../dashboard.php">User View</a></li>
            </ul>
        </div>
        <div class="content">
            <h2 class="mb-4">Manage Rooms</h2>
            <?php echo $message; ?>

            <div class="form-section">
                <h4>Add New Room / Edit Room</h4>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="room_id" id="roomId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="col-md-6">
                            <label for="room_type" class="form-label">Room Type</label>
                            <input type="text" class="form-control" id="room_type" name="room_type" required>
                        </div>
                        <div class="col-md-6">
                            <label for="price_per_night" class="form-label">Price Per Night</label>
                            <input type="number" step="0.01" class="form-control" id="price_per_night" name="price_per_night" required>
                        </div>
                        <div class="col-md-3">
                            <label for="capacity_adults" class="form-label">Adults Capacity</label>
                            <input type="number" class="form-control" id="capacity_adults" name="capacity_adults" required>
                        </div>
                        <div class="col-md-3">
                            <label for="capacity_children" class="form-label">Children Capacity</label>
                            <input type="number" class="form-control" id="capacity_children" name="capacity_children" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="image_url" class="form-label">Image URL</label>
                            <input type="text" class="form-control" id="image_url" name="image_url">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" id="submitButton">Add Room</button>
                            <button type="button" class="btn btn-secondary" id="resetButton" onclick="resetForm()">Clear</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-section">
                <h4>Existing Rooms</h4>
                <?php if (empty($rooms)): ?>
                    <p>No rooms found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room Number</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Adults</th>
                                    <th>Children</th>
                                    <th>Description</th>
                                    <th>Image URL</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['id']); ?></td>
                                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td>RS <?php echo htmlspecialchars($room['price_per_night']); ?></td>
                                        <td><?php echo htmlspecialchars($room['capacity_adults']); ?></td>
                                        <td><?php echo htmlspecialchars($room['capacity_children']); ?></td>
                                        <td><?php echo htmlspecialchars($room['description']); ?></td>
                                        <td><?php echo htmlspecialchars($room['image_url']); ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">Edit</button>
                                            <a href="?delete_id=<?php echo $room['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRoom(room) {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('roomId').value = room.id;
            document.getElementById('room_number').value = room.room_number;
            document.getElementById('room_type').value = room.room_type;
            document.getElementById('price_per_night').value = room.price_per_night;
            document.getElementById('capacity_adults').value = room.capacity_adults;
            document.getElementById('capacity_children').value = room.capacity_children;
            document.getElementById('description').value = room.description;
            document.getElementById('image_url').value = room.image_url;
            document.getElementById('submitButton').innerText = 'Update Room';
        }

        function resetForm() {
            document.getElementById('formAction').value = 'add';
            document.getElementById('roomId').value = '';
            document.getElementById('room_number').value = '';
            document.getElementById('room_type').value = '';
            document.getElementById('price_per_night').value = '';
            document.getElementById('capacity_adults').value = '';
            document.getElementById('capacity_children').value = '';
            document.getElementById('description').value = '';
            document.getElementById('image_url').value = '';
            document.getElementById('submitButton').innerText = 'Add Room';
        }
    </script>
</body>
</html>