<?php
// check_availability.php
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 1); // Display errors directly to the browser (for development)

session_start(); // Start the session. This MUST be the very first line after <?php

require_once 'db_config.php';

$available_rooms = [];
$error_message = "";
$check_in_date_str = "";
$check_out_date_str = "";
$adults = 1;
$children = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $check_in_date_str = $_POST['check_in_date'] ?? '';
    $check_out_date_str = $_POST['check_out_date'] ?? '';
    $adults = (int)($_POST['adults'] ?? 1);
    $children = (int)($_POST['children'] ?? 0);

    // Validate dates
    if (empty($check_in_date_str) || empty($check_out_date_str)) {
        $error_message = "<div class='alert alert-danger'>Please select both check-in and check-out dates.</div>";
    } else {
        try {
            $check_in_date_obj = new DateTime($check_in_date_str);
            $check_out_date_obj = new DateTime($check_out_date_str);
            $current_date_obj = new DateTime(date('Y-m-d')); // Get current date without time

            if ($check_in_date_obj < $current_date_obj) {
                $error_message = "<div class='alert alert-danger'>Check-in date cannot be in the past.</div>";
            } elseif ($check_out_date_obj <= $check_in_date_obj) {
                $error_message = "<div class='alert alert-danger'>Check-out date must be after check-in date.</div>";
            } elseif ($adults <= 0) {
                $error_message = "<div class='alert alert-danger'>Number of adults must be at least 1.</div>";
            } else {
                // Format dates for SQL query
                $sql_check_in = $check_in_date_obj->format('Y-m-d');
                $sql_check_out = $check_out_date_obj->format('Y-m-d');

                // SQL Query to find available rooms
                // Conditions:
                // 1. Room capacity meets guest requirements
                // 2. Room ID is NOT IN the list of booked rooms for the specified date range
                //    - Booked rooms are those with 'pending' or 'confirmed' status
                //    - Overlap condition: (existing check-in < requested check-out) AND (existing check-out > requested check-in)
                $sql = "SELECT id, room_number, room_type, description, price_per_night, capacity_adults, capacity_children, image_url
                        FROM rooms
                        WHERE capacity_adults >= ?
                        AND capacity_children >= ?
                        AND id NOT IN (
                            SELECT room_id FROM bookings
                            WHERE status IN ('pending', 'confirmed')
                            AND (
                                check_in_date < ? AND check_out_date > ?
                            )
                        )
                        ORDER BY price_per_night ASC";

                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error_message = "<div class='alert alert-danger'>Database query preparation failed: " . $conn->error . "</div>";
                } else {
                    $stmt->bind_param("iiss",
                        $adults,
                        $children,
                        $sql_check_out, // Parameter for check_in_date < ?
                        $sql_check_in   // Parameter for check_out_date > ?
                    );
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $available_rooms[] = $row;
                        }
                    } else {
                        $error_message = "<div class='alert alert-info'>No rooms available for the selected dates and guest count.</div>";
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $error_message = "<div class='alert alert-danger'>Date parsing error: " . $e->getMessage() . "</div>";
        }
    }
} else {
    // Set default check-in/out dates for initial display (e.g., today and tomorrow)
    $check_in_date_obj = new DateTime(date('Y-m-d'));
    $check_out_date_obj = new DateTime(date('Y-m-d'));
    $check_out_date_obj->modify('+1 day');
    $check_in_date_str = $check_in_date_obj->format('Y-m-d');
    $check_out_date_str = $check_out_date_obj->format('Y-m-d');
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Room Availability | Aura Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .availability-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
        .room-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .room-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .room-card .card-body {
            padding: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Aura Hotel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_bookings.php">My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link btn btn-primary ms-2" href="admin/index.php">Admin Panel</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="check_availability.php">Book a Room</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="availability-container">
            <h2 class="text-center mb-4">Check Room Availability</h2>
            <form action="check_availability.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="check_in_date" class="form-label">Check-in Date</label>
                        <input type="date" class="form-control" id="check_in_date" name="check_in_date"
                               value="<?php echo htmlspecialchars($check_in_date_str); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="check_out_date" class="form-label">Check-out Date</label>
                        <input type="date" class="form-control" id="check_out_date" name="check_out_date"
                               value="<?php echo htmlspecialchars($check_out_date_str); ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="adults" class="form-label">Adults</label>
                        <input type="number" class="form-control" id="adults" name="adults" min="1"
                               value="<?php echo htmlspecialchars($adults); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="children" class="form-label">Children</label>
                        <input type="number" class="form-control" id="children" name="children" min="0"
                               value="<?php echo htmlspecialchars($children); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Check Availability</button>
            </form>

            <hr class="my-4">

            <?php echo $error_message; ?>

            <?php if (!empty($available_rooms)): ?>
                <h3 class="mb-3">Available Rooms:</h3>
                <div class="row">
                    <?php foreach ($available_rooms as $room): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card room-card">
                                <img src="<?php echo htmlspecialchars($room['image_url'] ?: 'https://via.placeholder.com/400x200?text=Room+Image'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($room['room_type']); ?> (Room #<?php echo htmlspecialchars($room['room_number']); ?>)</h5>
                                    <p class="card-text"><?php echo htmlspecialchars($room['description']); ?></p>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item"><strong>Price:</strong> LKR <?php echo number_format($room['price_per_night'], 2); ?> / night</li>
                                        <li class="list-group-item"><strong>Capacity:</strong> <?php echo htmlspecialchars($room['capacity_adults']); ?> Adults, <?php echo htmlspecialchars($room['capacity_children']); ?> Children</li>
                                    </ul>
                                    <?php
                                        // Construct the URL for booking
                                        $book_url = "book_room.php?" . http_build_query([
                                            'room_id' => $room['id'],
                                            'check_in' => $check_in_date_str,
                                            'check_out' => $check_out_date_str,
                                            'adults' => $adults,
                                            'children' => $children,
                                            'price_per_night' => $room['price_per_night']
                                        ]);
                                    ?>
                                    <a href="<?php echo $book_url; ?>" class="btn btn-success w-100">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (empty($error_message) && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
                <div class="alert alert-info">No rooms found for the selected criteria.</div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>