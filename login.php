<?php
// login.php
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 1); // Display errors directly to the browser (for development)

session_start(); // Start the session. This MUST be the very first line after <?php

require_once 'db_config.php'; // Ensure this file exists and contains your database connection ($conn)

$email = $password = "";
$email_err = $password_err = $login_err = "";
$selected_login_role = "user"; // Default to user login

// Check if the user is already logged in, if yes then redirect to appropriate dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        header("location: admin/index.php"); // Redirect admin to admin panel
    } else {
        header("location: dashboard.php"); // Redirect regular user to user dashboard
    }
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get selected login role from form
    $selected_login_role = $_POST['login_as'] ?? 'user'; // Default to 'user' if not set

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Attempt to validate credentials
    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT id, username, email, password, role FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                // Check if email exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $email, $hashed_password, $db_role); // db_role is the actual role from DB
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, now check against selected role
                            if ($selected_login_role === "admin") {
                                if ($db_role === "admin") {
                                    // User selected admin login AND is an admin in DB
                                    session_regenerate_id();
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["user_id"] = $id;
                                    $_SESSION["username"] = $username;
                                    $_SESSION["email"] = $email;
                                    $_SESSION["role"] = $db_role;
                                    header("location: admin/index.php");
                                    exit;
                                } else {
                                    // User selected admin login BUT is NOT an admin in DB
                                    $login_err = "You do not have administrative privileges.";
                                }
                            } else {
                                // User selected user login (or default) - always redirect to dashboard
                               session_regenerate_id();
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["username"] = $username;
                                $_SESSION["email"] = $email;
                                $_SESSION["role"] = $db_role; // Store their actual role
                                header("location: dashboard.php");
                                exit;
                            }
                        } else {
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid email or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Aura Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div class="login-container">
       <h1><i>
    <span class="hotel-name"> Hotel Aura</span><br></i></h1>
    <h3>Login</h3>

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>
       
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
    <div class="mb-3">
        <label for="email" class="form-label float-start">Email address</label>
        <input type="email" id="email" name="email"
               class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
               value="<?php echo htmlspecialchars($email); ?>" required autocomplete="nope">
        <div class="invalid-feedback float-start"><?php echo $email_err; ?></div>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label float-start">Password</label>
        <input type="password" id="password" name="password"
               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
               required autocomplete="new-password"> <div class="invalid-feedback float-start"><?php echo $password_err; ?></div>
    </div>
    
    

            <div class="mb-4 text-start">
                <label class="form-label">Login as:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="login_as" id="loginUser" value="user"
                           <?php echo ($selected_login_role === 'user') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="loginUser">User</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="login_as" id="loginAdmin" value="admin"
                           <?php echo ($selected_login_role === 'admin') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="loginAdmin">Admin</label>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>