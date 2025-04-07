<?php
// Include the configuration file for the database connection
include('config.php');

// Start the session
session_start();

// Display error message if there's a login error
if (isset($_SESSION['error_message'])) {
    echo "<p style='color: red;'>".$_SESSION['error_message']."</p>";
    unset($_SESSION['error_message']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get login form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Sanitize the email to prevent SQL injection
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Prepare the SQL statement to fetch the user by email
    $sql = "SELECT id, fname, lname, email, pwd_hash, pwd_salt, admin FROM iss_persons WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify if user exists and password matches
    if ($user) {
        // Get the salt from the database
        $salt = $user['pwd_salt'];

        // Hash the entered password with the salt from the database
        $hashed_password = md5($password . $salt);

        // Compare the hashed password with the stored hash
        if ($hashed_password === $user['pwd_hash']) {
            // Successful login: Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fname'] . ' ' . $user['lname'];
            $_SESSION['user_role'] = $user['admin'] == 1 ? 'admin' : 'regular'; // Store the user's role

            // Redirect to the issues list page
            header("Location: issues_list.php"); // Redirect directly to issues_list.php
            exit();
        } else {
            // Password does not match
            $_SESSION['error_message'] = 'Invalid email or password.';
            header('Location: login.php');
            exit();
        }
    } else {
        // Email not found in the database
        $_SESSION['error_message'] = 'Invalid email or password.';
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <form action="login.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <input type="submit" value="Login">
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>
    
                
