<?php
session_start();
require 'config.php';
$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM iss_persons WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $id = $user['id'];
                $fname = $user['fname'];
                $lname = $user['lname'];
                $stored_hash = trim($user['pwd_hash']); 
                $stored_salt = trim($user['pwd_salt']);
                $admin = $user['admin'];

                
                $hashed_input_pwd = md5($password . $stored_salt);

               
                $log_data = "DEBUG LOGIN\nEmail: $email\nStored Hash: $stored_hash\nStored Salt: $stored_salt\nHashed Input Password: $hashed_input_pwd\n\n";
                file_put_contents('login_debug.log', $log_data, FILE_APPEND);

               
                if ($hashed_input_pwd !== $stored_hash) {
                    $debug_info = "Input Password: " . htmlspecialchars($password) . "<br>"
                                . "Stored Salt: " . htmlspecialchars($stored_salt) . "<br>"
                                . "Stored Hash: " . htmlspecialchars($stored_hash) . "<br>"
                                . "Hashed Input Password: " . htmlspecialchars($hashed_input_pwd) . "<br>";
                }

                if ($hashed_input_pwd === $stored_hash) {
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $fname . ' ' . $lname;
                    $_SESSION['email'] = $email;
                    $_SESSION['admin'] = $admin; 

                    Database::disconnect();
                    header("Location: issues_list.php"); 
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter both email and password.";
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>ISS2: Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="px-3 py-4 bg-light rounded shadow-sm">
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <?php if (!empty($debug_info)): ?>
            <div class="alert alert-warning mt-4">
                <strong>Debug Information:</strong><br>
                <?php echo $debug_info; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
