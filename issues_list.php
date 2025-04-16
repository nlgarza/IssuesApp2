<?php
session_start(); // If not already started
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_issue_id'])) {
    $issueIdToDelete = intval($_POST['delete_issue_id']);

    // Optional: Verify permissions again if needed
    // Connect to DB
    $pdo = Database::connect();

    // Delete the issue
    $stmt = $pdo->prepare("DELETE FROM iss_issues WHERE id = ?");
    $stmt->execute([$issueIdToDelete]);

    // Optional: Redirect or display a success message
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue_id'])) {
    $issueIdToUpdate = intval($_POST['update_issue_id']);

    // Collect fields from form (make sure these match your input names)
    $shortDesc = $_POST['short_description'];
    $longDesc = $_POST['long_description'];
    $openDate = $_POST['open_date'];
    $closeDate = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $perId = intval($_POST['per_id']);

    $pdo = Database::connect();

    // ✅ Proper UPDATE query
    $stmt = $pdo->prepare("UPDATE iss_issues 
        SET short_description = ?, long_description = ?, open_date = ?, close_date = ?, 
            priority = ?, org = ?, project = ?, per_id = ?
        WHERE id = ?");

    $stmt->execute([
        $shortDesc, $longDesc, $openDate, $closeDate,
        $priority, $org, $project, $perId, $issueIdToUpdate
    ]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

?>

<?php 

if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit(); 
}

$pdo = Database::connect();

// Fetch persons for dropdown list
$persons_sql = "SELECT id, fname, lname FROM iss_persons ORDER BY lname ASC";
$persons_stmt = $pdo->query($persons_sql);
$persons = $persons_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all issues
$sql = "SELECT * FROM iss_issues ORDER BY open_date DESC";
$issues = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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

  <table class="table table-striped table-sm mt-2">
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
          <!-- Everyone sees Read -->
          <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id'] ?>">R</button>

          <!-- Only admin or issue owner sees Update/Delete -->
          <?php if ($_SESSION['admin'] === "Y" || $_SESSION['user_id'] == $issue['per_id']): ?>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id'] ?>">U</button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $issue['id'] ?>">D</button>
          <?php endif; ?>
        </td>
      </tr>

      <!-- READ Modal -->
      <div class="modal fade" id="readIssue<?= $issue['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">Issue Details (ID <?= htmlspecialchars($issue['id']) ?>)</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
              <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>
              <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']) ?></p>
              <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']) ?></p>
              <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']) ?></p>
              <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']) ?></p>
              <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']) ?></p>
              <p><strong>Person Responsible (ID):</strong> <?= htmlspecialchars($issue['per_id']) ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- UPDATE Modal (Admin or Owner Only) -->
      <?php if ($_SESSION['admin'] === "Y" || $_SESSION['user_id'] == $issue['per_id']): ?>
        <div class="modal fade" id="updateIssue<?= $issue['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header bg-warning">
                <h5 class="modal-title">Update Issue (ID <?= $issue['id'] ?>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form method="POST">
                  <input type="hidden" name="update_issue_id" value="<?= $issue['id'] ?>">

                  <label>Short Description</label>
                  <input type="text" name="short_description" class="form-control mb-2" value="<?= htmlspecialchars($issue['short_description']) ?>" required>

                  <label>Long Description</label>
                  <textarea name="long_description" class="form-control mb-2"><?= htmlspecialchars($issue['long_description']) ?></textarea>

                  <label>Open Date</label>
                  <input type="date" name="open_date" class="form-control mb-2" value="<?= $issue['open_date'] ?>">

                  <label>Close Date</label>
                  <input type="date" name="close_date" class="form-control mb-2" value="<?= $issue['close_date'] ?>">

                  <label>Priority</label>
                  <input type="text" name="priority" class="form-control mb-2" value="<?= htmlspecialchars($issue['priority']) ?>">

                  <label>Org</label>
                  <input type="text" name="org" class="form-control mb-2" value="<?= htmlspecialchars($issue['org']) ?>">

                  <label>Project</label>
                  <input type="text" name="project" class="form-control mb-2" value="<?= htmlspecialchars($issue['project']) ?>">

                  <label>Person Responsible</label>
                  <select name="per_id" class="form-control mb-3" required>
                    <?php foreach ($persons as $person): ?>
                      <option value="<?= $person['id']; ?>" <?= $person['id'] == $issue['per_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($person['lname'] . ', ' . $person['fname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <button type="submit" class="btn btn-warning">Update</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- DELETE Modal (Admin or Owner Only) -->
<?php if ($_SESSION['admin'] === "Y" || $_SESSION['user_id'] == $issue['per_id']): ?>
  <div class="modal fade" id="deleteIssue<?= $issue['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Delete Issue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete issue ID <strong><?= $issue['id'] ?></strong>?</p>
          <form method="POST">
            <input type="hidden" name="delete_issue_id" value="<?= $issue['id'] ?>">
            <button type="submit" class="btn btn-danger">Yes, Delete</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>


    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Issue Modal (Admins only) -->
<?php if ($_SESSION['admin'] === "Y"): ?>
  <div class="modal fade" id="addIssueModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Add New Issue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="issues_create.php" enctype="multipart/form-data">
            <label>Short Description</label>
            <input type="text" name="short_description" class="form-control mb-2" required>

            <label>Long Description</label>
            <textarea name="long_description" class="form-control mb-2"></textarea>

            <label>Open Date</label>
            <input type="date" name="open_date" class="form-control mb-2" value="<?= date('Y-m-d'); ?>" required>

            <label>Close Date</label>
            <input type="date" name="close_date" class="form-control mb-2">

            <label>Priority</label>
            <input type="text" name="priority" class="form-control mb-2">

            <label>Org</label>
            <input type="text" name="org" class="form-control mb-2">

            <label>Project</label>
            <input type="text" name="project" class="form-control mb-2">

            <label>Person Responsible</label>
            <select name="per_id" class="form-control mb-3" required>
              <?php foreach ($persons as $person): ?>
                <option value="<?= $person['id']; ?>">
                  <?= htmlspecialchars($person['lname'] . ', ' . $person['fname']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <label>PDF Attachment</label>
            <input type="file" name="pdf_attachment" class="form-control mb-2" accept="application/pdf" />

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

<?php Database::disconnect(); ?>




            
