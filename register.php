<?php
// register.php
session_start(); // Start the session for messages

require_once 'db_config.php'; // Include database connection

$registration_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Input validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $registration_message = "<div class='alert alert-danger'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_message = "<div class='alert alert-danger'>Invalid email format.</div>";
    } elseif ($password !== $confirm_password) {
        $registration_message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } elseif (strlen($password) < 6) {
        $registration_message = "<div class='alert alert-danger'>Password must be at least 6 characters long.</div>";
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $registration_message = "<div class='alert alert-danger'>Username or Email already exists.</div>";
        } else {
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $registration_message = "<div class='alert alert-success'>Registration successful! You can now <a href='login.php'>login</a>.</div>";
            } else {
                $registration_message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Aura Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">
            <span class="hotel-name">Aura Hotel</span><br>
            Register
        </h2>
        <?php echo $registration_message; ?>
        <form action="register.php" method="POST">
    <div class="mb-3">
        <label for="username" class="form-label float-start">Username</label>
        <input type="text" class="form-control" id="username" name="username" required autocomplete="off">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label float-start">Email address</label>
        <input type="email" class="form-control" id="email" name="email" required autocomplete="off">
    </div>
    <div class="mb-3">
        <label for="password" class="form-label float-start">Password</label>
        <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
    </div>
    <div class="mb-3">
        <label for="confirm_password" class="form-label float-start">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary w-100">Register</button>
    <p class="text-center mt-3 register-link">Already have an account? <a href="login.php">Login here</a></p>
</form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>