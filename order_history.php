<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

// Initialize variables
$sort_query = "ORDER BY order_date DESC";
$search_query = "";
$search_term = "";

// Handle sorting
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
    if ($sort == 'date_asc') {
        $sort_query = "ORDER BY order_date ASC";
    } elseif ($sort == 'name_az') {
        $sort_query = "ORDER BY customer_name ASC";
    } elseif ($sort == 'name_za') {
        $sort_query = "ORDER BY customer_name DESC";
    }
}

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_query = "WHERE (orders.order_id LIKE '%$search_term%' 
                      OR customers.customer_name LIKE '%$search_term%' 
                      OR orders.total LIKE '%$search_term%')";
}

// Fetch orders with customer names
$query = "SELECT orders.*, customers.customer_name 
          FROM orders 
          LEFT JOIN customers ON orders.customer_id = customers.customer_id 
          $search_query
          $sort_query";

$orders = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Order History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
    }
    .action-buttons {
      white-space: nowrap;
    }
    .search-container {
      margin-bottom: 20px;
    }
    .search-form {
      display: flex;
      gap: 10px;
    }
    .search-input {
      flex-grow: 1;
      max-width: 400px;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2 class="text-center mb-4">Order History</h2>

  <div class="search-container">
    <form method="get" class="search-form">
      <input type="text" name="search" class="form-control search-input" 
             placeholder="Search by Order ID, Customer Name or Amount" 
             value="<?= htmlspecialchars($search_term) ?>">
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if (!empty($search_term)): ?>
        <a href="order_history.php" class="btn btn-outline-secondary">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="mb-3 d-flex justify-content-between">
    <div>
      <a href="?sort=date_desc<?= !empty($search_term) ? '&search='.urlencode($search_term) : '' ?>" 
         class="btn btn-outline-secondary btn-sm">Sort by New</a>
      <a href="?sort=date_asc<?= !empty($search_term) ? '&search='.urlencode($search_term) : '' ?>" 
         class="btn btn-outline-secondary btn-sm">Sort by Old</a>
      <a href="?sort=name_az<?= !empty($search_term) ? '&search='.urlencode($search_term) : '' ?>" 
         class="btn btn-outline-secondary btn-sm">Customer A–Z</a>
      <a href="?sort=name_za<?= !empty($search_term) ? '&search='.urlencode($search_term) : '' ?>" 
         class="btn btn-outline-secondary btn-sm">Customer Z–A</a>
      <a href="order_history.php" class="btn btn-outline-dark btn-sm">Show All Orders</a>
    </div>
    <a href="dashboard.php" class="btn btn-primary btn-sm">← Back to Dashboard</a>
  </div>

  <?php if ($orders->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Order ID</th>
          <th>Customer Name</th>
          <th>Total</th>
          <th>Received</th>
          <th>Return</th>
          <th>Order Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($order = $orders->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($order['order_id']) ?></td>
            <td><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></td>
            <td>Rs. <?= number_format($order['total'], 2) ?></td>
            <td>Rs. <?= number_format($order['received_amount'], 2) ?></td>
            <td>Rs. <?= number_format($order['return_amount'], 2) ?></td>
            <td><?= date('d M Y h:i A', strtotime($order['order_date'])) ?></td>
            <td class="action-buttons">
              <a href="bill.php?order_id=<?= $order['order_id'] ?>" class="btn btn-info btn-sm">View Bill</a>
              <a href="update_order.php?order_id=<?= $order['order_id'] ?>" class="btn btn-warning btn-sm">Update Bill</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">No orders found<?= !empty($search_term) ? ' matching your search' : '' ?>.</div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>