<?php
// Start the session to store error messages and other session-based info
session_start();
require '../database/database.php';
// Define variables and initialize them to empty values
$email = $password = " ";
$email_err = $password_err = " ";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If there are no errors, proceed to validate the user credentials
    if (empty($email_err) && empty($password_err)) {
        // Include database connection
        require_once "config.php"; // Make sure you have the config file with your DB connection settings

        try {
            // Prepare the SQL statement to get the user by email
            $sql = "SELECT id, fname, lname, mobile, email, pwd_hash, pwd_salt, admin FROM iss_persons WHERE email = :email";
            $stmt = $pdo->prepare($sql);

            // Bind the email parameter to the prepared statement
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);

            // Execute the statement
            $stmt->execute();

            // Check if user exists
            if ($stmt->rowCount() == 1) {
                // Fetch the result
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get the stored salt and password hash
                $db_salt = $user['pwd_salt'];
                $db_password_hash = $user['pwd_hash'];

                // Combine the stored salt and the user-entered password
                $hashed_password = md5($password . $db_salt);

                // Compare the generated hash with the stored password hash
                if ($hashed_password === $db_password_hash) {
                    // Password is correct, start a new session
                    session_start();

                    // Store user data in session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["email"] = $user['email'];
                    $_SESSION["fname"] = $user['fname'];
                    $_SESSION["lname"] = $user['lname'];
                    $_SESSION["mobile"] = $user['mobile'];
                    $_SESSION["admin"] = $user['admin'];  // Store admin status

                    // Redirect to the issues list page
                    header("location: issues_list.php");
                    exit();
                } else {
                    // Display an error message if password is incorrect
                    $password_err = "The password you entered is incorrect.";
                }
            } else {
                // If email does not exist
                $email_err = "No account found with that email.";
            }
        } catch (PDOException $e) {
            // Display an error message if something goes wrong with the database query
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Department Status Report (DSR)</title>
</head>
<body>

    <h2>Login</h2>

    <p>Please enter your email and password to log in.</p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo $email; ?>" required>
            <span><?php echo $email_err; ?></span>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <span><?php echo $password_err; ?></span>
        </div>
        <div>
            <input type="submit" value="Login">
        </div>
    </form>

</body>
</html>