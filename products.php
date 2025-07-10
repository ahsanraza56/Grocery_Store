<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $company = !empty($_POST['company']) ? $_POST['company'] : NULL;
    $expiry = !empty($_POST['expiry']) ? $_POST['expiry'] : NULL;
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("INSERT INTO products (product_name, company, expiry_date, quantity, unit, price_per_unit) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisd", $name, $company, $expiry, $quantity, $unit, $price);
    $stmt->execute();
    $stmt->close();
    header("Location: products.php");
    exit;
}

// Delete product
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE product_id = $id");
    header("Location: products.php");
    exit;
}

// Search functionality
$search = '';
$where = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where = "WHERE product_name LIKE '%$search%' OR company LIKE '%$search%'";
}

// Get all products
$products = $conn->query("SELECT * FROM products $where ORDER BY product_id DESC");

// Define available units
$units = ['pcs', 'kg', 'litres', 'dozen', 'grams', 'ml', 'pack', 'box', 'bottle', 'can', 'bag', 'carton'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Products - Grocery Store</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .search-container {
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    .search-box {
      position: relative;
      max-width: 800px;
      margin: 0 auto;
    }
    .search-input {
      padding: 1rem 1.5rem;
      padding-left: 3.5rem;
      border-radius: 50px;
      border: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      font-size: 1.1rem;
      width: 100%;
      transition: all 0.3s;
    }
    .search-input:focus {
      outline: none;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    .search-icon {
      position: absolute;
      left: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
      font-size: 1.2rem;
    }
    .search-btn {
      position: absolute;
      right: 5px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s;
    }
    .search-btn:hover {
      background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
      transform: translateY(-50%) scale(1.05);
    }
    .reset-btn {
      position: absolute;
      right: 130px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: #6c757d;
      cursor: pointer;
    }
    .reset-btn:hover {
      color: #dc3545;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <h2>Manage Products</h2>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

  <!-- Add Product Form -->
  <form method="POST" class="card p-4 mb-4">
    <h5 class="mb-3">Add New Product</h5>
    <div class="row">
      <div class="col-md-4 mb-3">
        <input type="text" name="name" class="form-control" placeholder="Product Name" required>
      </div>
      <div class="col-md-4 mb-3">
        <input type="text" name="company" class="form-control" placeholder="Company (optional)">
      </div>
      <div class="col-md-4 mb-3">
        <input type="date" name="expiry" class="form-control" placeholder="Expiry Date (optional)">
      </div>
      <div class="col-md-3 mb-3">
        <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
      </div>
      <div class="col-md-3 mb-3">
        <select name="unit" class="form-select" required>
          <option value="">Select Unit</option>
          <?php foreach ($units as $u): ?>
            <option value="<?= $u ?>"><?= $u ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 mb-3">
        <input type="number" step="0.01" name="price" class="form-control" placeholder="Price per unit" required>
      </div>
      <div class="col-md-3 mb-3 d-grid">
        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
      </div>
    </div>
  </form>

  <!-- Search Bar -->
  <div class="search-container">
    <form method="GET" action="products.php" class="search-box">
      <i class="fas fa-search search-icon"></i>
      <input type="text" name="search" class="search-input" placeholder="Search products by name or company..." value="<?= htmlspecialchars($search) ?>">
      <?php if (!empty($search)): ?>
        <button type="button" class="reset-btn" onclick="window.location.href='products.php'">
          <i class="fas fa-times"></i>
        </button>
      <?php endif; ?>
      <button type="submit" class="search-btn">
        <i class="fas fa-search"></i> Search
      </button>
    </form>
  </div>

  <!-- Product Table -->
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Company</th>
        <th>Expiry</th>
        <th>Qty</th>
        <th>Unit</th>
        <th>Price</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $products->fetch_assoc()): ?>
        <tr>
          <td><?= $row['product_id'] ?></td>
          <td><?= htmlspecialchars($row['product_name']) ?></td>
          <td><?= htmlspecialchars($row['company'] ?? '-') ?></td>
          <td><?= $row['expiry_date'] ?: '-' ?></td>
          <td><?= $row['quantity'] ?></td>
          <td><?= $row['unit'] ?></td>
          <td><?= number_format($row['price_per_unit'], 2) ?></td>
          <td>
            <a href="update_product.php?id=<?= $row['product_id'] ?>" class="btn btn-sm btn-warning">Update</a>
            <a href="products.php?delete=<?= $row['product_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>