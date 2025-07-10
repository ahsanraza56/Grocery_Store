<?php
$conn = new mysqli("localhost", "root", "", "grocery_store");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$order_id = $_GET['order_id'] ?? 0;

// Fetch order and customer data
$order_sql = "
  SELECT o.*, c.customer_name, c.phone 
  FROM orders o 
  JOIN customers c ON o.customer_id = c.customer_id 
  WHERE o.order_id = $order_id
";

$order_result = $conn->query($order_sql);
$order = $order_result->fetch_assoc();

if (!$order) {
  echo "<div class='error-message'>Order not found or invalid Order ID.</div>";
  exit;
}

// Fetch order items
$items = $conn->query("
  SELECT oi.quantity, oi.price_per_unit, p.product_name 
  FROM order_items oi 
  JOIN products p ON oi.product_id = p.product_id 
  WHERE oi.order_id = $order_id
");
?>
<!DOCTYPE html>
<html>
<head>
  <title>POS Receipt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary: #4CAF50;
      --secondary: #FF9800;
      --dark: #333;
      --light: #f9f9f9;
      --shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    body { 
      font-family: 'Poppins', sans-serif; 
      max-width: 380px; 
      margin: 20px auto; 
      background: var(--light); 
      color: var(--dark); 
      padding: 20px;
      box-shadow: var(--shadow);
      border-radius: 12px;
    }
    
    .print-only { display: none; }
    
    @media print {
      .no-print { display: none; }
      .print-only { display: block; }
      body { 
        box-shadow: none; 
        border-radius: 0;
        padding: 10px;
        max-width: 300px;
      }
    }
    
    .header {
      text-align: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px dashed var(--primary);
    }
    
    .store-name {
      font-size: 24px;
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 5px;
    }
    
    .store-slogan {
      font-size: 12px;
      color: var(--secondary);
      font-weight: 300;
    }
    
    .receipt-icon {
      font-size: 40px;
      color: var(--primary);
      margin-bottom: 10px;
    }
    
    .section-title {
      font-weight: 600;
      color: var(--primary);
      margin: 15px 0 8px;
      font-size: 16px;
    }
    
    .divider {
      border-top: 1px dashed #ddd;
      margin: 15px 0;
    }
    
    .bold { font-weight: 600; }
    .right { text-align: right; }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 10px 0;
    }
    
    th {
      text-align: left;
      padding: 8px 0;
      border-bottom: 1px solid #eee;
      color: var(--primary);
    }
    
    td {
      padding: 6px 0;
      vertical-align: top;
    }
    
    .item-name {
      font-weight: 500;
    }
    
    .item-details {
      font-size: 12px;
      color: #666;
    }
    
    .total-section {
      background: #f5f5f5;
      padding: 12px;
      border-radius: 8px;
      margin: 15px 0;
    }
    
    .total-row {
      display: flex;
      justify-content: space-between;
      margin: 8px 0;
    }
    
    .grand-total {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary);
    }
    
    .footer {
      text-align: center;
      margin-top: 20px;
      font-size: 12px;
      color: #888;
    }
    
    .qr-code {
      width: 80px;
      height: 80px;
      margin: 15px auto;
      background: #eee;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ccc;
    }
    
    .btn-dashboard {
      display: block;
      width: 100%;
      padding: 12px;
      background: var(--primary);
      color: white;
      text-align: center;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      margin: 20px 0;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s;
    }
    
    .btn-dashboard:hover {
      background: #3d8b40;
      transform: translateY(-2px);
    }
    
    .error-message {
      background: #ffebee;
      color: #c62828;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
      margin: 50px auto;
      max-width: 400px;
      box-shadow: var(--shadow);
    }
  </style>
</head>
<body>
  <div class="no-print">
    <a href="dashboard.php" class="btn-dashboard">
      <i class="fas fa-tachometer-alt"></i> Back to Dashboard
    </a>
  </div>

  <div class="header">
    <div class="receipt-icon">
      <i class="fas fa-receipt"></i>
    </div>
    <div class="store-name">FreshMart Grocery</div>
    <div class="store-slogan">Quality Products at Affordable Prices</div>
  </div>

  <div class="section-title">ORDER DETAILS</div>
  <table>
    <tr>
      <td>Order ID</td>
      <td class="right">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
    </tr>
    <tr>
      <td>Customer</td>
      <td class="right"><?= $order['customer_name'] ?></td>
    </tr>
    <tr>
      <td>Phone</td>
      <td class="right"><?= $order['phone'] ?></td>
    </tr>
    <tr>
      <td>Date</td>
      <td class="right"><?= date('M j, Y h:i A', strtotime($order['order_date'])) ?></td>
    </tr>
  </table>

  <div class="divider"></div>

  <div class="section-title">ITEMS PURCHASED</div>
  <table>
    <?php while ($row = $items->fetch_assoc()): ?>
      <tr>
        <td class="item-name"><?= $row['product_name'] ?></td>
        <td class="right"><?= number_format($row['quantity'] * $row['price_per_unit'], 2) ?></td>
      </tr>
      <tr>
        <td colspan="2" class="item-details">
          <?= $row['quantity'] ?> x <?= number_format($row['price_per_unit'], 2) ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <div class="divider"></div>

  <div class="total-section">
    <div class="total-row">
      <span>Subtotal:</span>
      <span>$<?= number_format($order['total'], 2) ?></span>
    </div>
    <div class="total-row">
      <span>Discount (<?= $order['discount_percent'] ?>%):</span>
      <span>-$<?= number_format($order['total'] * $order['discount_percent'] / 100, 2) ?></span>
    </div>
    <div class="total-row">
      <span>Tax:</span>
      <span>$0.00</span>
    </div>
    <div class="total-row grand-total">
      <span>Total:</span>
      <span>$<?= number_format($order['total'] - ($order['total'] * $order['discount_percent'] / 100), 2) ?></span>
    </div>
    <div class="total-row">
      <span>Amount Paid:</span>
      <span>$<?= number_format($order['received_amount'], 2) ?></span>
    </div>
    <div class="total-row">
      <span>Change:</span>
      <span>$<?= number_format($order['return_amount'], 2) ?></span>
    </div>
  </div>

  <div class="print-only">
    <div class="qr-code">
      <i class="fas fa-qrcode"></i>
    </div>
  </div>

  <div class="footer">
    <p>Thank you for shopping with us!</p>
    <p>For returns, please bring this receipt within 7 days</p>
    <p>Contact: (123) 456-7890 | info@freshmart.com</p>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Auto-print only when coming from POS system
      if(window.location.search.includes('autoprint')) {
        window.print();
      }
    });
  </script>
</body>
</html>