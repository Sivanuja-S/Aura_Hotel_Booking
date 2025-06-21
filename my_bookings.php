<?php
// my_bookings.php
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$bookings = [];
$message = "";

// Check for a success message from the booking process
if (isset($_SESSION['booking_success_message'])) {
    $message = $_SESSION['booking_success_message'];
    unset($_SESSION['booking_success_message']); // Clear it after displaying
}

// Fetch user's bookings
$sql = "SELECT b.*, r.room_number, r.room_type, r.price_per_night, r.image_url
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $message = "<div class='alert alert-danger'>Error fetching bookings: " . $conn->error . "</div>";
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $message .= "<div class='alert alert-info'>You have no bookings yet.</div>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Aura Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            padding-top: 50px;
        }
        .booking-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .booking-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">My Bookings</h2>
        <?php echo $message; ?>

        <?php if (!empty($bookings)): ?>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card booking-card">
                            <img src="<?php echo htmlspecialchars($booking['image_url'] ?: 'https://via.placeholder.com/400x150?text=Room+Image'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($booking['room_type']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($booking['room_type']); ?> (Room #<?php echo htmlspecialchars($booking['room_number']); ?>)</h5>
                                <p class="card-text">
                                    Booking ID: <strong>#<?php echo htmlspecialchars($booking['id']); ?></strong><br>
                                    Check-in: <strong><?php echo htmlspecialchars($booking['check_in_date']); ?></strong><br>
                                    Check-out: <strong><?php echo htmlspecialchars($booking['check_out_date']); ?></strong><br>
                                    Total Price: <strong>LKR <?php echo number_format($booking['total_price'], 2); ?></strong><br>
                                    Status: <span class="badge <?php
                                        switch($booking['status']) {
                                            case 'confirmed': echo 'bg-success'; break;
                                            case 'pending': echo 'bg-warning text-dark'; break;
                                            case 'cancelled': echo 'bg-danger'; break;
                                            case 'completed': echo 'bg-info'; break;
                                            default: echo 'bg-secondary';
                                        }
                                    ?>"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></span>
                                </p>
                                </div>
                            <div class="card-footer text-muted">
                                Booked on: <?php echo date('Y-m-d H:i', strtotime($booking['booking_date'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">Ready to book a room? <a href="check_availability.php">Check availability now!</a></p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>