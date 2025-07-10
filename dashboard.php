<?php 
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Grocery Store Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f1f1f1; }
    .card { margin: 20px 0; }
    .nav-link { font-size: 1.1rem; }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php">Grocery Store Admin</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="products.php">Products</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="new_order.php">New Order</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="order_history.php">Order History</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="customers.php">Customers</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Dashboard Content -->
  <div class="container mt-4">
    <h2 class="mb-4">Welcome to the Admin Dashboard</h2>
    <div class="row">
      <!-- Products Card -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-body text-center">
            <h5 class="card-title">Manage Products</h5>
            <p class="card-text">Add, update or remove products.</p>
            <a href="products.php" class="btn btn-primary">Go to Products</a>
          </div>
        </div>
      </div>
      <!-- New Order Card -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-body text-center">
            <h5 class="card-title">New Order</h5>
            <p class="card-text">Create new orders and generate bills.</p>
            <a href="new_order.php" class="btn btn-success">Create Order</a>
          </div>
        </div>
      </div>
      <!-- Order History Card -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-body text-center">
            <h5 class="card-title">Order History</h5>
            <p class="card-text">View all past orders and details.</p>
            <a href="order_history.php" class="btn btn-warning">View Orders</a>
          </div>
        </div>
      </div>
      <!-- Customers Card -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-body text-center">
            <h5 class="card-title">Customers</h5>
            <p class="card-text">Manage customer information and details.</p>
            <a href="customers.php" class="btn btn-info">Manage Customers</a>
          </div>
        </div>
      </div>
      
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
