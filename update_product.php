<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch product details
$product_result = $conn->query("SELECT * FROM products WHERE product_id = $id");
if ($product_result->num_rows == 0) {
    echo "Product not found.";
    exit;
}

$product = $product_result->fetch_assoc();

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['product_name']);
    $company = !empty($_POST['company']) ? $conn->real_escape_string($_POST['company']) : null;
    $expiry = !empty($_POST['expiry_date']) ? $conn->real_escape_string($_POST['expiry_date']) : null;
    $quantity = intval($_POST['quantity']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $price = floatval($_POST['price_per_unit']);

    $update_query = "UPDATE products SET 
        product_name = '$name',
        company = " . ($company !== null ? "'$company'" : "NULL") . ",
        expiry_date = " . ($expiry !== null ? "'$expiry'" : "NULL") . ",
        quantity = $quantity,
        unit = '$unit',
        price_per_unit = $price
        WHERE product_id = $id";

    if ($conn->query($update_query)) {
        $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                      <i class="bi bi-check-circle-fill me-2"></i> Product updated successfully!
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        // Refresh product info
        $product_result = $conn->query("SELECT * FROM products WHERE product_id = $id");
        $product = $product_result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <i class="bi bi-exclamation-triangle-fill me-2"></i> Error updating product: ' . $conn->error . '
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Update Product | Inventory Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
  <style>
    :root {
      --primary-color: #4e73df;
      --secondary-color: #f8f9fc;
      --accent-color: #2e59d9;
      --text-dark: #5a5c69;
    }
    
    body {
      background-color: #f8f9fc;
      color: var(--text-dark);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .card {
      border-radius: 0.5rem;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
      border: none;
    }
    
    .card-header {
      background-color: var(--primary-color);
      color: white;
      border-radius: 0.5rem 0.5rem 0 0 !important;
      padding: 1.25rem 1.5rem;
    }
    
    .form-control, .form-select {
      border-radius: 0.35rem;
      padding: 0.75rem 1rem;
      border: 1px solid #d1d3e2;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 0.5rem 1.5rem;
      border-radius: 0.35rem;
    }
    
    .btn-primary:hover {
      background-color: var(--accent-color);
      border-color: var(--accent-color);
    }
    
    .btn-outline-secondary {
      border-radius: 0.35rem;
      padding: 0.5rem 1.5rem;
    }
    
    .page-title {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
    
    .product-image-container {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background-color: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      overflow: hidden;
      border: 3px solid var(--primary-color);
    }
    
    .product-image-container i {
      font-size: 4rem;
      color: var(--primary-color);
    }
    
    .optional-field {
      position: relative;
    }
    
    .optional-field::after {
      content: "(Optional)";
      position: absolute;
      right: 10px;
      top: 38px;
      font-size: 0.8rem;
      color: #6c757d;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">
              <i class="bi bi-pencil-square me-2"></i>Update Product
            </h3>
            <a href="dashboard.php" class="btn btn-sm btn-outline-light">
              <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
          </div>
          
          <div class="card-body p-4">
            <?= $message ?? '' ?>
            
            <div class="text-center mb-4">
              <div class="product-image-container">
                <i class="bi bi-box-seam"></i>
              </div>
              <h4 class="page-title"><?= htmlspecialchars($product['product_name']) ?></h4>
            </div>
            
            <form method="POST" class="needs-validation" novalidate>
              <div class="row g-3">
                <div class="col-md-12">
                  <label for="product_name" class="form-label">Product Name *</label>
                  <input type="text" class="form-control" id="product_name" name="product_name" 
                         value="<?= htmlspecialchars($product['product_name']) ?>" required>
                  <div class="invalid-feedback">
                    Please provide a product name.
                  </div>
                </div>
                
                <div class="col-md-6 optional-field">
                  <label for="company" class="form-label">Manufacturer/Company</label>
                  <input type="text" class="form-control" id="company" name="company" 
                         value="<?= htmlspecialchars($product['company'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 optional-field">
                  <label for="expiry_date" class="form-label">Expiry Date</label>
                  <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                         value="<?= htmlspecialchars($product['expiry_date'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                  <label for="quantity" class="form-label">Quantity *</label>
                  <input type="number" class="form-control" id="quantity" name="quantity" 
                         min="0" value="<?= (int)$product['quantity'] ?>" required>
                  <div class="invalid-feedback">
                    Please provide a valid quantity.
                  </div>
                </div>
                
                <div class="col-md-4">
                  <label for="unit" class="form-label">Unit *</label>
                  <select class="form-select" id="unit" name="unit" required>
                    <option value="">Select unit</option>
                    <option value="pcs" <?= ($product['unit'] ?? '') == 'pcs' ? 'selected' : '' ?>>Pieces</option>
                    <option value="kg" <?= ($product['unit'] ?? '') == 'kg' ? 'selected' : '' ?>>Kilograms</option>
                    <option value="g" <?= ($product['unit'] ?? '') == 'g' ? 'selected' : '' ?>>Grams</option>
                    <option value="l" <?= ($product['unit'] ?? '') == 'l' ? 'selected' : '' ?>>Liters</option>
                    <option value="ml" <?= ($product['unit'] ?? '') == 'ml' ? 'selected' : '' ?>>Milliliters</option>
                    <option value="box" <?= ($product['unit'] ?? '') == 'box' ? 'selected' : '' ?>>Box</option>
                    <option value="pack" <?= ($product['unit'] ?? '') == 'pack' ? 'selected' : '' ?>>Pack</option>
                    <option value="bottle" <?= ($product['unit'] ?? '') == 'Bottle' ? 'selected' : '' ?>>Bottle</option>
                  </select>
                  <div class="invalid-feedback">
                    Please select a unit.
                  </div>
                </div>
                
                <div class="col-md-4">
                  <label for="price_per_unit" class="form-label">Price (Rs.) *</label>
                  <div class="input-group">
                    <span class="input-group-text">Rs.</span>
                    <input type="number" step="0.01" class="form-control" id="price_per_unit" 
                           name="price_per_unit" min="0" value="<?= number_format((float)($product['price_per_unit'] ?? 0), 2) ?>" required>
                  </div>
                  <div class="invalid-feedback">
                    Please provide a valid price.
                  </div>
                </div>
                
                <div class="col-12 mt-4">
                  <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                      <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-check-circle me-1"></i> Update Product
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
          
          <div class="card-footer bg-light text-muted">
            <small>Last updated: <?= date('F j, Y, g:i a', strtotime($product['updated_at'] ?? 'now')) ?></small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    (function () {
      'use strict'
      
      var forms = document.querySelectorAll('.needs-validation')
      
      Array.prototype.slice.call(forms)
        .forEach(function (form) {
          form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
              event.preventDefault()
              event.stopPropagation()
            }
            
            form.classList.add('was-validated')
          }, false)
        })
    })()
  </script>
</body>
</html>