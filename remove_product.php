<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: remove_product.php?deleted=1");
    exit;
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Remove Product - Grocery Store</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    function confirmDelete(name, id) {
      if (confirm("Are you sure you want to delete '" + name + "' from stock?")) {
        window.location.href = "remove_product.php?delete=" + id;
      }
    }
  </script>
</head>
<body>
<div class="container mt-5">
  <h2 class="text-center mb-4">Remove Product from Stock</h2>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Product deleted successfully!</div>
  <?php endif; ?>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Company</th>
        <th>Expiry Date</th>
        <th>Quantity</th>
        <th>Unit</th>
        <th>Price/Unit</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $products->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['company']) ?></td>
          <td><?= htmlspecialchars($row['expiry']) ?></td>
          <td><?= $row['quantity'] ?></td>
          <td><?= htmlspecialchars($row['unit']) ?></td>
          <td>Rs. <?= number_format($row['price'], 2) ?></td>
          <td>
            <button onclick="confirmDelete('<?= htmlspecialchars($row['name']) ?>', <?= $row['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="text-end">
    <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
  </div>
</div>
</body>
</html>
