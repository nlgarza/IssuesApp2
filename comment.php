<?php
session_start();
require 'config.php';

// Check if the issue_id is provided in the URL
if (!isset($_GET['issue_id'])) {
    die("Issue ID is required.");
}

$issueId = intval($_GET['issue_id']);  


$pdo = Database::connect();


$stmt = $pdo->prepare("SELECT c.*, p.fname, p.lname 
                       FROM iss_comments c 
                       JOIN iss_persons p ON c.per_id = p.id 
                       WHERE c.iss_id = ? 
                       ORDER BY c.posted_date DESC");
$stmt->execute([$issueId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM iss_issues WHERE id = ?");
$stmt->execute([$issueId]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Issue Comments</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-3">
  <h3>Comments for Issue ID: <?= htmlspecialchars($issue['id']) ?></h3>
  <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
  <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>

  <!-- Display comments -->
  <hr>
  <h5>Comments:</h5>
  <?php if (!empty($comments)): ?>
    <ul class="list-group mb-2">
      <?php foreach ($comments as $comment): ?>
        <li class="list-group-item">
          <strong><?= htmlspecialchars($comment['fname'] . ' ' . $comment['lname']) ?></strong> 
          on <em><?= htmlspecialchars(date("M d, Y H:i", strtotime($comment['posted_date']))) ?></em><br>
          <strong><?= htmlspecialchars($comment['short_comment']) ?></strong><br>
          <small><?= nl2br(htmlspecialchars($comment['long_comment'])) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-muted">No comments yet.</p>
  <?php endif; ?>

  <!-- Add Comment Form -->
  <hr>
  <h6>Add a Comment:</h6>
  <form method="POST" action="add_comment.php">
    <input type="hidden" name="issue_id" value="<?= $issueId ?>">
    <div class="mb-3">
      <label for="short_comment" class="form-label">Short Comment</label>
      <input type="text" class="form-control" name="short_comment" id="short_comment" required>
    </div>
    <div class="mb-3">
      <label for="long_comment" class="form-label">Long Comment</label>
      <textarea class="form-control" name="long_comment" id="long_comment" rows="3"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Submit Comment</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
