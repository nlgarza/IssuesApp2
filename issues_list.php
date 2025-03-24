<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch the issues from the database, sorted by project name
// Database connection (same as before)
$pdo = new PDO("mysql:host=localhost;dbname=your_database_name", "your_db_user", "your_db_password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM issues ORDER BY project_name");
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List</title>
</head>
<body>
    <h2>Issues List</h2>
    
    <table>
        <tr>
            <th>Project Name</th>
            <th>Issue</th>
            <th>Status</th>
        </tr>
        <?php foreach ($issues as $issue): ?>
            <tr>
                <td><?php echo htmlspecialchars($issue['project_name']); ?></td>
                <td><?php echo htmlspecialchars($issue['issue']); ?></td>
                <td><?php echo htmlspecialchars($issue['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
