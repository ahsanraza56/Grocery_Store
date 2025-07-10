<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

// Get order ID
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    echo "Invalid order ID";
    exit;
}

// Get the order using correct column name
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Order not found with ID: " . $order_id;
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Get customer name
$customer_name = "Customer ID: " . $order['customer_id'];
$customer_phone = "";

$customer_stmt = $conn->prepare("SELECT customer_name, phone FROM customers WHERE customer_id = ?");
$customer_stmt->bind_param("i", $order['customer_id']);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
if ($customer_result->num_rows > 0) {
    $customer_data = $customer_result->fetch_assoc();
    $customer_name = $customer_data['customer_name'];
    $customer_phone = $customer_data['phone'];
}
$customer_stmt->close();

// Fetch order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.product_name, p.unit 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// Calculate subtotal from order_item table
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['quantity'] * $item['price_per_unit'];
}

// Get values from order table with correct column names
$discount_percent = floatval($order['discount_percent']);
$discount_amount = ($subtotal * $discount_percent) / 100;
$final_total = $subtotal - $discount_amount;

// Payment data from order table
$received_amount = floatval($order['received_amount']);
$return_amount = floatval($order['return_amount']);

// Order date
$order_date = $order['order_date'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order_id ?> - Grocery Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .container { max-width: none !important; margin: 0 !important; padding: 0 !important; }
            body { font-size: 12px; background: white !important; }
            .invoice-card { border: none !important; box-shadow: none !important; }
        }
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .invoice-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .invoice-header {
            background: linear-gradient(135deg, #6772e5, #00a8ff);
            color: white;
            padding: 2.5rem;
            margin-bottom: 30px;
            position: relative;
        }
        .store-logo {
            font-size: 2.8rem;
            margin-bottom: 10px;
        }
        .store-name {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .store-details {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .invoice-watermark {
            position: absolute;
            right: 30px;
            top: 30px;
            opacity: 0.1;
            font-size: 6rem;
            font-weight: bold;
            transform: rotate(-15deg);
        }
        .invoice-details-box {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border-left: 4px solid #6772e5;
        }
        .customer-details-box {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border-left: 4px solid #00a8ff;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #6772e5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 10px;
        }
        .invoice-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .invoice-table thead th {
            background-color: #6772e5;
            color: white;
            font-weight: 500;
            padding: 12px 15px;
        }
        .invoice-table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .invoice-table tbody tr:last-child td {
            border-bottom: none;
        }
        .invoice-table tbody tr:hover {
            background-color: rgba(103, 114, 229, 0.05);
        }
        .total-section {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            margin-top: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }
        .total-row {
            font-size: 1.1rem;
            padding: 8px 0;
        }
        .final-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6772e5;
            border-top: 2px dashed #e0e0e0;
            padding-top: 15px;
            margin-top: 15px;
        }
        .badge-quantity {
            background-color: #e1f0ff;
            color: #00a8ff;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .payment-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .invoice-footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px dashed #ddd;
        }
        .action-btn {
            padding: 10px 25px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .action-btn i {
            margin-right: 8px;
        }
        .product-unit {
            font-size: 0.8rem;
            color: #888;
            font-style: italic;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #ddd, transparent);
            margin: 25px 0;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="invoice-card bg-white">
        <div class="invoice-header text-center position-relative">
            <div class="invoice-watermark">INVOICE</div>
            <div class="store-logo">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <h1 class="store-name">Fresh Grocery Mart</h1>
            <div class="store-details">
                <p class="mb-1">123 Market Street, City, State 12345</p>
                <p class="mb-1"><i class="fas fa-phone-alt me-2"></i>(555) 123-4567 | <i class="fas fa-envelope me-2"></i>info@freshgrocery.com</p>
                <p class="mb-0"><i class="fas fa-globe me-2"></i>www.freshgrocery.com</p>
            </div>
        </div>

        <div class="px-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="invoice-details-box">
                        <h3 class="section-title"><i class="fas fa-file-invoice"></i> Invoice Details</h3>
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%"><strong>Invoice #</strong></td>
                                <td>: <span class="badge bg-primary"><?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Date & Time</strong></td>
                                <td>: <?= date('F j, Y h:i A', strtotime($order_date)) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cashier</strong></td>
                                <td>: <?= htmlspecialchars($_SESSION['admin']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="customer-details-box">
                        <h3 class="section-title"><i class="fas fa-user"></i> Customer Details</h3>
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%"><strong>Customer Name</strong></td>
                                <td>: <?= htmlspecialchars($customer_name) ?></td>
                            </tr>
                            <?php if (!empty($customer_phone)): ?>
                            <tr>
                                <td><strong>Phone</strong></td>
                                <td>: <?= htmlspecialchars($customer_phone) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Customer Type</strong></td>
                                <td>: Regular Customer</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="40%">Product Description</th>
                            <th width="15%" class="text-center">Qty</th>
                            <th width="20%" class="text-end">Unit Price</th>
                            <th width="20%" class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <div class="product-unit">per <?= htmlspecialchars($item['unit']) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge-quantity"><?= number_format($item['quantity'], 2) ?></span>
                            </td>
                            <td class="text-end">Rs. <?= number_format($item['price_per_unit'], 2) ?></td>
                            <td class="text-end">
                                <strong>Rs. <?= number_format($item['quantity'] * $item['price_per_unit'], 2) ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-exclamation-circle me-2"></i>No items found in this order
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="payment-info">
                        <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Payment Method:</strong></td>
                                <td class="text-end">Cash</td>
                            </tr>
                            <tr>
                                <td><strong>Amount Received:</strong></td>
                                <td class="text-end text-success">Rs. <?= number_format($received_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Change Returned:</strong></td>
                                <td class="text-end text-info">Rs. <?= number_format($return_amount, 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="total-section">
                        <table class="table table-borderless">
                            <tr class="total-row">
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-end">Rs. <?= number_format($subtotal, 2) ?></td>
                            </tr>
                            <?php if ($discount_percent > 0): ?>
                            <tr class="total-row">
                                <td><strong>Discount (<?= $discount_percent ?>%):</strong></td>
                                <td class="text-end text-danger">- Rs. <?= number_format($discount_amount, 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="final-total">
                                <td><strong>GRAND TOTAL:</strong></td>
                                <td class="text-end">Rs. <?= number_format($final_total, 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="invoice-footer">
                <p class="mb-2">Thank you for shopping with us! <i class="fas fa-heart text-danger"></i></p>
                <p class="mb-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Items: <?= count($items) ?> | 
                        Total Qty: <?= !empty($items) ? number_format(array_sum(array_column($items, 'quantity')), 2) : '0.00' ?>
                    </small>
                </p>
                <p class="mb-0"><small>This is computer generated receipt and does not require signature</small></p>
            </div>

            <div class="text-center my-4 no-print">
                    <a href="print.php?order_id=<?= $order['order_id'] ?>" class="action-btn btn btn-outline-secondary me-4">
                    <i class="fas fa-tachometer-alt"></i>Print
                </a>
                
                <a href="dashboard.php" class="action-btn btn btn-outline-secondary me-3">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="new_order.php" class="action-btn btn btn-success">
                    <i class="fas fa-plus-circle"></i> New Order
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto print when loaded (optional)
    // window.onload = function() { window.print(); }
</script>
</body>
</html>