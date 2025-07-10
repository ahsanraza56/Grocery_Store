<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

$customer_id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    die("Customer not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['customer_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name && $email && $phone) {
        $stmt = $conn->prepare("UPDATE customers SET customer_name=?, email=?, phone=?, address=? WHERE customer_id=?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);
        if ($stmt->execute()) {
            $success = "Customer updated successfully.";
        } else {
            $error = "Error updating customer.";
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
  <title>Edit Customer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4">Edit Customer</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>Name *</label>
      <input type="text" name="customer_name" value="<?= htmlspecialchars($customer['customer_name']) ?>" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Email *</label>
      <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Phone *</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Address</label>
      <input type="text" name="address" value="<?= htmlspecialchars($customer['address']) ?>" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Update Customer</button>
    <a href="customers.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
