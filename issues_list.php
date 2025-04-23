<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// === HANDLE FORM ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::connect();

    if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        $issueId = intval($_POST['issue_id']);
        $shortComment = trim($_POST['short_comment']);
        $longComment = trim($_POST['long_comment']);
        $perId = $_SESSION['user_id'];

        if (!empty($shortComment)) {
            $stmt = $pdo->prepare("INSERT INTO iss_comments (iss_id, per_id, short_comment, long_comment, posted_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$issueId, $perId, $shortComment, $longComment]);
        }

        Database::disconnect();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_issue') {
        if ($_SESSION['admin'] !== "Y") {
            die("Unauthorized");
        }

        $shortDesc = trim($_POST['short_description']);
        $longDesc = trim($_POST['long_description']);
        $openDate = $_POST['open_date'];
        $closeDate = $_POST['close_date'] ?: null;
        $priority = trim($_POST['priority']);
        $org = trim($_POST['org']);
        $project = trim($_POST['project']);
        $perId = intval($_POST['per_id']);

        $stmt = $pdo->prepare("INSERT INTO iss_issues 
            (short_description, long_description, open_date, close_date, priority, org, project, per_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$shortDesc, $longDesc, $openDate, $closeDate, $priority, $org, $project, $perId]);

        Database::disconnect();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['update_issue_id'])) {
        $id = intval($_POST['update_issue_id']);
        $stmt = $pdo->prepare("UPDATE iss_issues 
            SET short_description = ?, long_description = ?, open_date = ?, close_date = ?, 
                priority = ?, org = ?, project = ?, per_id = ?
            WHERE id = ?");
        $stmt->execute([
            $_POST['short_description'], $_POST['long_description'], $_POST['open_date'],
            $_POST['close_date'], $_POST['priority'], $_POST['org'], $_POST['project'],
            intval($_POST['per_id']), $id
        ]);

        Database::disconnect();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['delete_issue_id'])) {
        $id = intval($_POST['delete_issue_id']);
        $stmt = $pdo->prepare("DELETE FROM iss_issues WHERE id = ?");
        $stmt->execute([$id]);

        Database::disconnect();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// === FETCH DATA ===
$pdo = Database::connect();

$persons = $pdo->query("SELECT id, fname, lname FROM iss_persons ORDER BY lname ASC")->fetchAll(PDO::FETCH_ASSOC);
$issues = $pdo->query("SELECT * FROM iss_issues ORDER BY open_date DESC")->fetchAll(PDO::FETCH_ASSOC);

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ISS2: Issues List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-3">
  <h2 class="text-center">Issues List</h2>

  <div class="d-flex justify-content-between align-items-center mt-3">
    <h3>All Issues</h3>
    <div class="d-flex gap-2">
      <a href="logout.php" class="btn btn-warning">Logout</a>

      <?php if ($_SESSION['admin'] === "Y"): ?>
        <a href="register.php" class="btn btn-primary">Register New User</a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIssueModal">Add Issue</button>
      <?php endif; ?>
    </div>
  </div>

  <table class="table table-striped mt-3">
    <thead class="table-dark">
    <tr>
      <th>ID</th>
      <th>Short Description</th>
      <th>Open Date</th>
      <th>Close Date</th>
      <th>Priority</th>
      <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($issues as $issue): ?>
      <tr>
        <td><?= htmlspecialchars($issue['id']) ?></td>
        <td><?= htmlspecialchars($issue['short_description']) ?></td>
        <td><?= htmlspecialchars($issue['open_date']) ?></td>
        <td><?= htmlspecialchars($issue['close_date']) ?></td>
        <td><?= htmlspecialchars($issue['priority']) ?></td>
        <td>
          <!-- View -->
          <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id'] ?>">R</button>
          <?php if ($_SESSION['admin'] === "Y" || $_SESSION['user_id'] == $issue['per_id']): ?>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id'] ?>">U</button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $issue['id'] ?>">D</button>
          <?php endif; ?>
        </td>
      </tr>

      <!-- READ Modal -->
      <div class="modal fade" id="readIssue<?= $issue['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">Issue #<?= $issue['id'] ?> Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p><strong>Short:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
              <p><strong>Long:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>
              <p><strong>Dates:</strong> <?= $issue['open_date'] ?> → <?= $issue['close_date'] ?></p>
              <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']) ?></p>
              <p><strong>Org:</strong> <?= htmlspecialchars($issue['org']) ?> | <strong>Project:</strong> <?= htmlspecialchars($issue['project']) ?></p>

              <hr>
              <h5>Comments</h5>

              <!-- Comment Form -->
              <form method="POST" class="mb-3">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                <input type="text" name="short_comment" class="form-control mb-1" placeholder="Short Comment" required>
                <textarea name="long_comment" class="form-control mb-2" placeholder="Long Comment (optional)"></textarea>
                <button type="submit" class="btn btn-outline-primary btn-sm">Post Comment</button>
              </form>

              <?php
              $pdo = Database::connect();
              $stmt = $pdo->prepare("SELECT c.*, p.fname, p.lname FROM iss_comments c JOIN iss_persons p ON c.per_id = p.id WHERE c.iss_id = ? ORDER BY posted_date DESC");
              $stmt->execute([$issue['id']]);
              $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
              Database::disconnect();

              if ($comments):
                foreach ($comments as $comment): ?>
                  <div class="border rounded p-2 mb-2 bg-light">
                    <strong><?= htmlspecialchars($comment['fname']) ?> <?= htmlspecialchars($comment['lname']) ?></strong>
                    <small class="text-muted"><?= date("M d, Y H:i", strtotime($comment['posted_date'])) ?></small><br>
                    <strong><?= htmlspecialchars($comment['short_comment']) ?></strong><br>
                    <?= nl2br(htmlspecialchars($comment['long_comment'])) ?>
                  </div>
                <?php endforeach;
              else:
                echo '<p class="text-muted">No comments yet.</p>';
              endif;
              ?>
            </div>
          </div>
        </div>
      </div>

      <!-- UPDATE Modal -->
      <!-- ... [same as before, you can re-add from your previous code] -->

      <!-- DELETE Modal -->
      <!-- ... [same as before] -->

    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Issue Modal -->
<?php if ($_SESSION['admin'] === "Y"): ?>
  <div class="modal fade" id="addIssueModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Add New Issue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <input type="hidden" name="action" value="add_issue">
            <input type="text" name="short_description" class="form-control mb-2" placeholder="Short Description" required>
            <textarea name="long_description" class="form-control mb-2" placeholder="Long Description"></textarea>
            <input type="date" name="open_date" class="form-control mb-2" value="<?= date('Y-m-d'); ?>" required>
            <input type="date" name="close_date" class="form-control mb-2">
            <input type="text" name="priority" class="form-control mb-2" placeholder="Priority">
            <input type="text" name="org" class="form-control mb-2" placeholder="Org">
            <input type="text" name="project" class="form-control mb-2" placeholder="Project">
            <select name="per_id" class="form-control mb-3" required>
              <?php foreach ($persons as $person): ?>
                <option value="<?= $person['id']; ?>"><?= htmlspecialchars($person['lname'] . ', ' . $person['fname']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-success">Add Issue</button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
