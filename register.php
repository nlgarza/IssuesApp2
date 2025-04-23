<?php 
session_start();
require 'config.php';
$pdo = Database::connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] == 'Y' ? 'Y' : '0'; 

    if (!empty($fname) && !empty($lname) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if (!preg_match('/@svsu\.edu$/i', $email)) {
            $error = "You must register with an @svsu.edu email address.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM iss_persons WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "An account with that email already exists.";
            } else {
                $salt = bin2hex(random_bytes(8));
                $pwd_hash = md5($password . $salt); // hashed password

               //Inserting user into the database
                $stmt = $pdo->prepare("INSERT INTO iss_persons (fname, lname, email, pwd_hash, pwd_salt, admin) 
                                       VALUES (:fname, :lname, :email, :pwd_hash, :pwd_salt, :admin)"); 
                $stmt->bindParam(':fname', $fname);
                $stmt->bindParam(':lname', $lname);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':pwd_hash', $pwd_hash);
                $stmt->bindParam(':pwd_salt', $salt);
                $stmt->bindParam(':admin', $is_admin);

                if ($stmt->execute()) {
                    $success = "Registration successful. You can now log in.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>ISS2: Register New User</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST" action="register.php" class="px-3 py-4">
    <div class="mb-3">
        <label for="fname" class="form-label">First Name:</label>
        <input type="text" class="form-control" id="fname" name="fname" required>
    </div>

    <div class="mb-3">
        <label for="lname" class="form-label">Last Name:</label>
        <input type="text" class="form-control" id="lname" name="lname" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email:</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password:</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>

    <div class="mb-3">
        <label for="confirm_password" class="form-label">Verify Password:</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
    </div>

    <!-- Checkbox for Admin user -->
    <div class="mb-3">
        <label for="is_admin" class="form-label">Admin:</label>
        <input type="checkbox" name="is_admin" value="Y">
        <small>Check this box to register as an admin (if applicable).</small>
    </div>

    <button type="submit" class="btn btn-success">Register</button>
</form>

</div>
</body>
</html>
