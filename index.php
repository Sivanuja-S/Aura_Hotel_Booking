<?php
// aura_hotel_app/index.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PHP array of your specified image paths for the rotating background
$imagePaths = [
    'assets/images/aura4.jpg',
    'assets/images/aura2.jpg',
    'assets/images/aura3.jpg',
    'assets/images/aura5.jpg',
    'assets/images/aura6.jpg'
];

// PHP to select an initial random image (JavaScript will take over from here)
$randomIndex = array_rand($imagePaths);
$initialBackgroundImage = $imagePaths[$randomIndex];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Aura Hotel | Your Perfect Stay in Sri Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa; /* Fallback/general background */
            margin: 0; /* Remove default body margin */
        }
        .navbar {
            background-color: #343a40;
            padding: 15px 0;
        }
        .navbar-brand {
            color: #ffffff !important;
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            color: #ffffff !important;
        }
        .navbar-nav .nav-link:hover {
            color: #f8f9fa !important;
        }
        .hero-section {
            background-image: url('<?php echo htmlspecialchars($initialBackgroundImage); ?>'); /* Initial image */
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            color: #ffffff;
            text-align: center;
            padding: 150px 20px;
            min-height: 100vh; /* Make it take the full viewport height */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            /* Optional: Add a subtle transition for smoother background changes */
            /* transition: background-image 0.5s ease-in-out; */
        }
        .hero-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.65); /* Darker overlay for better text contrast */
            z-index: 1;
        }
        .hero-content {
            z-index: 2; /* Ensure content is above overlay */
            max-width: 90%; /* Constrain width for readability */
        }
        .hero-content h1 {
            font-size: 3.8rem; /* Slightly larger heading */
            margin-bottom: 20px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.9); /* Stronger text shadow */
        }
        .hero-content p {
            font-size: 1.5rem; /* Larger paragraph text */
            max-width: 800px;
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
            line-height: 1.6;
        }
        .hero-content .btn {
            font-size: 1.6rem; /* Larger button text */
            padding: 18px 35px; /* Larger button padding */
            border-radius: 50px; /* Rounded button */
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .hero-content .btn:hover {
            transform: translateY(-3px); /* Subtle hover effect */
        }
        .message-section {
            text-align: center;
            padding: 20px;
            font-size: 1.8rem; /* Even larger font size for quote */
            font-weight: bold;
            color: #FFD700; /* Bright gold color for high visibility */
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 1); /* Stronger text shadow for more pop */
            z-index: 2; /* Ensure it's above the overlay */
            position: relative; /* Needed for z-index to work */
            margin-top: 40px; /* More space from button */
            letter-spacing: 1px; /* Add slight letter spacing */
        }
        .book-now-button-container {
             text-align: center;
             margin-top: 40px; /* Adjusted margin to push button down */
         }
        /* Adjusted footer to stick to the bottom if content is short */
        .footer {
            background-color: #343a40;
            color: #ffffff;
            text-align: center;
            padding: 20px 0;
            width: 100%;
            /* Removed fixed positioning to allow it to be at the bottom of the content */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Aura Hotel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book_room.php">Book a Room</a>
                        </li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/index.php">Admin Panel</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login / Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section" id="heroSection">
        <div class="hero-content">
            <h1>Welcome to Aura Hotel</h1>
            <p>Experience comfort, elegance, and unmatched hospitality in the heart of the city.</p>
            <p><strong>Your Perfect Stay Begins Here</strong></p>
            <div class="book-now-button-container">
                <a href="login.php" class="btn btn-primary btn-lg">Book Your Stay Now</a>
            </div>
        </div>
        <div class="message-section">
            <b>Relax. Recharge. Reimagine.</b>
        </div>
    </header>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Aura Hotel. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const heroSection = document.getElementById('heroSection');
            // Image paths from PHP passed to JavaScript
            const imagePathsJs = <?php echo json_encode($imagePaths); ?>;
            let currentIndex = 0;

            function changeBackground() {
                currentIndex = (currentIndex + 1) % imagePathsJs.length;
                heroSection.style.backgroundImage = `url('${imagePathsJs[currentIndex]}')`;
            }

            // Change background every 3 second (3000 milliseconds)
            setInterval(changeBackground, 3000);
        });
    </script>
</body>
</html>