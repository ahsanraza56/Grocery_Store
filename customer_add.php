<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['customer_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name && $email && $phone) {
        $stmt = $conn->prepare("INSERT INTO customers (customer_name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $address);
        if ($stmt->execute()) {
            $success = "Customer added successfully.";
        } else {
            $error = "Error adding customer.";
        }
        $stmt->close();
    } else {
        $error = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Customer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4">Add New Customer</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>Name *</label>
      <input type="text" name="customer_name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Email *</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Phone *</label>
      <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Address</label>
      <input type="text" name="address" class="form-control">
    </div>
    <button type="submit" class="btn btn-success">Add Customer</button>
    <a href="customers.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
