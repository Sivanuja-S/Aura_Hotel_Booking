<?php
// aura_hotel_app/book_room.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db_config.php'; // Correct path to db_config.php

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$message = '';
$rooms = []; // To store available rooms

// --- Fetch all rooms to display for booking ---
$sql_fetch_rooms = "SELECT id, room_number, room_type, price_per_night, capacity_adults, capacity_children, description, image_url FROM rooms ORDER BY room_number ASC";
if ($result_rooms = $conn->query($sql_fetch_rooms)) {
    if ($result_rooms->num_rows > 0) {
        while ($row = $result_rooms->fetch_assoc()) {
            $rooms[] = $row;
        }
    } else {
        $message .= "<div class='alert alert-info'>No rooms are currently available for booking. Please add rooms via the Admin Panel.</div>";
    }
    $result_rooms->free();
} else {
    $message .= "<div class='alert alert-danger'>Error fetching rooms: " . $conn->error . "</div>";
}


// --- Handle Booking Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_room'])) {
    $room_id = trim($_POST['room_id']);
    $check_in_date = trim($_POST['check_in_date']);
    $check_out_date = trim($_POST['check_out_date']);

    // Basic validation
    if (empty($room_id) || empty($check_in_date) || empty($check_out_date)) {
        $message = "<div class='alert alert-danger'>Please fill in all booking details.</div>";
    } elseif ($check_in_date >= $check_out_date) {
        $message = "<div class='alert alert-danger'>Check-out date must be after check-in date.</div>";
    } else {
        // Fetch room price for calculation
        $sql_price = "SELECT price_per_night FROM rooms WHERE id = ?";
        $stmt_price = $conn->prepare($sql_price);
        if ($stmt_price === false) {
            $message = "<div class='alert alert-danger'>Error preparing price statement: " . $conn->error . "</div>";
        } else {
            $stmt_price->bind_param("i", $room_id);
            $stmt_price->execute();
            $result_price = $stmt_price->get_result();
            $room_data = $result_price->fetch_assoc();
            $price_per_night = $room_data['price_per_night'] ?? 0; // Default to 0 if room not found
            $stmt_price->close();

            if ($price_per_night == 0) {
                $message = "<div class='alert alert-danger'>Selected room not found or has no price.</div>";
            } else {
                // Calculate number of nights
                $datetime1 = new DateTime($check_in_date);
                $datetime2 = new DateTime($check_out_date);
                $interval = $datetime1->diff($datetime2);
                $number_of_nights = $interval->days;

                if ($number_of_nights <= 0) {
                    $message = "<div class='alert alert-danger'>Booking must be for at least one night.</div>";
                } else {
                    $total_price = $price_per_night * $number_of_nights;

                    // --- Check for Room Availability (Overlap Check) ---
                    // A room is unavailable if an existing booking for the same room_id overlaps with the new dates
                    // Overlap conditions:
                    // (new_check_in < existing_check_out) AND (new_check_out > existing_check_in)
                    $sql_check_availability = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ('pending', 'confirmed') AND (
                        (check_in_date < ? AND check_out_date > ?) OR  -- Existing booking overlaps start of new booking
                        (check_in_date < ? AND check_out_date > ?) OR  -- Existing booking overlaps end of new booking
                        (? <= check_in_date AND ? >= check_out_date)  -- New booking fully within existing booking
                    )";
                    $stmt_check = $conn->prepare($sql_check_availability);
                    if ($stmt_check === false) {
                        $message = "<div class='alert alert-danger'>Error preparing availability statement: " . $conn->error . "</div>";
                    } else {
                        // Bind parameters: room_id, check_out, check_in, check_out, check_in, check_in, check_out
                        $stmt_check->bind_param("issssss", $room_id, $check_out_date, $check_in_date, $check_in_date, $check_out_date, $check_in_date, $check_out_date);
                        $stmt_check->execute();
                        $stmt_check->bind_result($overlap_count);
                        $stmt_check->fetch();
                        $stmt_check->close();

                        if ($overlap_count > 0) {
                            $message = "<div class='alert alert-warning'>This room is not available for the selected dates. Please choose different dates or another room.</div>";
                        } else {
                            // --- Insert Booking into Database ---
                            $sql_insert = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status, booking_date) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                            $stmt_insert = $conn->prepare($sql_insert);
                            if ($stmt_insert === false) {
                                $message = "<div class='alert alert-danger'>Error preparing insert statement: " . $conn->error . "</div>";
                            } else {
                                $stmt_insert->bind_param("iisss", $user_id, $room_id, $check_in_date, $check_out_date, $total_price);
                                if ($stmt_insert->execute()) {
                                    $message = "<div class='alert alert-success'>Booking successful! Your booking is pending confirmation.</div>";
                                    // Optional: Redirect to dashboard after successful booking
                                    // header("location: dashboard.php?booking_success=true");
                                    // exit;
                                } else {
                                    $message = "<div class='alert alert-danger'>Error creating booking: " . $stmt_insert->error . "</div>";
                                }
                                $stmt_insert->close();
                            }
                        }
                    }
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room | Aura Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .navbar { background-color: #343a40; padding: 15px 0; }
        .navbar-brand { color: #ffffff !important; font-weight: bold; }
        .navbar-nav .nav-link { color: #ffffff !important; }
        .navbar-nav .nav-link:hover { color: #f8f9fa !important; }
        .booking-container {
            padding: 40px;
            max-width: 900px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 { color: #333; margin-bottom: 30px; }
        .room-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            background-color: #fff;
        }
        .room-card img {
            max-width: 100%;
            height: 200px; /* Fixed height for consistency */
            object-fit: cover; /* Ensures image covers the area */
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Aura Hotel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="book_room.php">Book a Room</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/index.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <h2 class="text-center">Book Your Stay</h2>
        <?php echo $message; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="check_in_date" class="form-label">Check-in Date</label>
                    <input type="date" class="form-control" id="check_in_date" name="check_in_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="check_out_date" class="form-label">Check-out Date</label>
                    <input type="date" class="form-control" id="check_out_date" name="check_out_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="room_id" class="form-label">Select Room</label>
                <select class="form-select" id="room_id" name="room_id" required>
                    <option value="">Choose a room...</option>
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo htmlspecialchars($room['id']); ?>">
                                <?php echo htmlspecialchars($room['room_number'] . " - " . $room['room_type'] . " (Price: $" . $room['price_per_night'] . " / Day)"); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="book_room" class="btn btn-primary btn-lg">Confirm Booking</button>
            </div>
        </form>

        <hr class="my-5">

        <h3 class="mb-4 text-center">Available Rooms</h3>
        <?php if (empty($rooms)): ?>
            <p class="text-center">No rooms to display. Please add rooms via the Admin Panel.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-4">
                        <div class="room-card">
                            <?php if (!empty($room['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($room['image_url']); ?>" class="img-fluid" alt="Room Photo <?php echo htmlspecialchars($room['room_number']); ?>">
                            <?php else: ?>
                                <img src="images/default_room.jpg" class="img-fluid" alt="No Room Photo">
                            <?php endif; ?>
                            <h5>Room <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['room_type']); ?></h5>
                            <p>Price: $<?php echo htmlspecialchars($room['price_per_night']); ?> / Day</p>
                            <p>Capacity: <?php echo htmlspecialchars($room['capacity_adults']); ?> Adults, <?php echo htmlspecialchars($room['capacity_children']); ?> Children</p>
                            <p class="text-muted"><small><?php echo htmlspecialchars($room['description']); ?></small></p>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectRoom(<?php echo htmlspecialchars($room['id']); ?>)">Book This Room</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to set minimum date for check-in
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            const checkInDateInput = document.getElementById('check_in_date');
            const checkOutDateInput = document.getElementById('check_out_date');

            // Set min for check-in to today
            checkInDateInput.min = today.toISOString().split('T')[0];

            // Set min for check-out to at least tomorrow
            checkOutDateInput.min = tomorrow.toISOString().split('T')[0];

            // Update check-out min dynamically based on check-in
            checkInDateInput.addEventListener('change', function() {
                const checkInVal = new Date(this.value);
                const nextDay = new Date(checkInVal);
                nextDay.setDate(nextDay.getDate() + 1);
                checkOutDateInput.min = nextDay.toISOString().split('T')[0];

                // If check-out is before or same as check-in, reset it
                if (new Date(checkOutDateInput.value) <= checkInVal) {
                    checkOutDateInput.value = nextDay.toISOString().split('T')[0];
                }
            });
        });

        // Function to pre-select a room when "Book This Room" button is clicked
        function selectRoom(roomId) {
            document.getElementById('room_id').value = roomId;
            // Optionally scroll to the top of the form
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>