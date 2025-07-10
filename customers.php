<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

// Search filter
$search = $_GET['search'] ?? '';
$search_sql = '';
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_sql = "WHERE customer_name LIKE '%$search_safe%' OR email LIKE '%$search_safe%' OR phone LIKE '%$search_safe%'";
}

// Sorting
$sort_query = "ORDER BY customer_name ASC";
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
    if ($sort == 'name_za') {
        $sort_query = "ORDER BY customer_name DESC";
    } elseif ($sort == 'phone') {
        $sort_query = "ORDER BY phone ASC";
    } elseif ($sort == 'email') {
        $sort_query = "ORDER BY email ASC";
    }
}

$customers = $conn->query("SELECT * FROM customers $search_sql $sort_query");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="text-center mb-4">Customers</h2>

  <div class="mb-3 d-flex justify-content-between align-items-center">
    <form class="d-flex" method="get" action="">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control form-control-sm me-2" placeholder="Search by name, email, phone">
      <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
    </form>

    <div>
      <a href="?sort=name_az" class="btn btn-outline-secondary btn-sm">Name A–Z</a>
      <a href="?sort=name_za" class="btn btn-outline-secondary btn-sm">Name Z–A</a>
      <a href="?sort=phone" class="btn btn-outline-secondary btn-sm">Sort by Phone</a>
      <a href="?sort=email" class="btn btn-outline-secondary btn-sm">Sort by Email</a>
      <a href="customers.php" class="btn btn-outline-dark btn-sm">Show All</a>
    </div>

    <div>
      <a href="customer_add.php" class="btn btn-success btn-sm">+ Add Customer</a>
      <a href="dashboard.php" class="btn btn-primary btn-sm">← Dashboard</a>
    </div>
  </div>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($customers->num_rows > 0): ?>
        <?php while ($customer = $customers->fetch_assoc()): ?>
          <tr>
            <td><?= $customer['customer_id'] ?></td>
            <td><?= htmlspecialchars($customer['customer_name']) ?></td>
            <td><?= htmlspecialchars($customer['email']) ?></td>
            <td><?= htmlspecialchars($customer['phone']) ?></td>
            <td><?= htmlspecialchars($customer['address']) ?></td>
            <td>
              <a href="customer_edit.php?id=<?= $customer['customer_id'] ?>" class="btn btn-sm btn-warning">Update</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="6" class="text-center">No customers found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
