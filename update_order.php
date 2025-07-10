<?php
// Database connection
$host = '127.0.0.1';
$db   = 'grocery_store';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Initialize variables
$order_id = $_GET['order_id'] ?? null;
$message = '';
$error = '';

// Fetch order details
$order = [];
$order_items = [];
$customers = [];
$products = [];

if ($order_id) {
    // Get order information
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $error = "Order not found.";
    } else {
        // Get order items
        $stmt = $pdo->prepare("SELECT oi.*, p.product_name, p.price_per_unit as original_price 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.product_id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
        
        // Get all customers for dropdown
        $stmt = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name");
        $customers = $stmt->fetchAll();
        
        // Get all products for dropdown
        $stmt = $pdo->query("SELECT product_id, product_name, price_per_unit FROM products ORDER BY product_name");
        $products = $stmt->fetchAll();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order_id) {
    try {
        $pdo->beginTransaction();
        
        // Update order details
        $customer_id = $_POST['customer_id'];
        $discount_percent = $_POST['discount_percent'] ?? 0;
        $additional_amount = $_POST['received_amount'] ?? 0;
        
        // Validate inputs
        if ($discount_percent < 0 || $discount_percent > 100) {
            throw new Exception("Discount must be between 0% and 100%");
        }
        
        if ($additional_amount < 0) {
            throw new Exception("Received amount cannot be negative");
        }
        
        // Calculate new received amount (add to existing)
        $received_amount = $order['received_amount'] + $additional_amount;
        
        $stmt = $pdo->prepare("UPDATE orders 
                              SET customer_id = ?, discount_percent = ?, received_amount = ?
                              WHERE order_id = ?");
        $stmt->execute([$customer_id, $discount_percent, $received_amount, $order_id]);
        
        // Delete existing order items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Add new order items
        $product_ids = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price_per_unit'] ?? [];
        
        $total = 0;
        for ($i = 0; $i < count($product_ids); $i++) {
            if (!empty($product_ids[$i])) {
                try {
                    // Validate and sanitize inputs
                    $product_id = filter_var($product_ids[$i], FILTER_VALIDATE_INT);
                    $quantity = !empty($quantities[$i]) ? max(1, (int)$quantities[$i]) : 1;
                    $price = !empty($prices[$i]) ? max(0, (float)$prices[$i]) : 0;
                    
                    if ($product_id === false || $product_id <= 0) {
                        throw new Exception("Invalid product ID");
                    }
                    
                    if ($price <= 0) {
                        // Get original price if custom price is invalid
                        $stmt = $pdo->prepare("SELECT price_per_unit FROM products WHERE product_id = ?");
                        $stmt->execute([$product_id]);
                        $product = $stmt->fetch();
                        
                        if ($product) {
                            $price = $product['price_per_unit'];
                        } else {
                            throw new Exception("Product not found");
                        }
                    }
                    
                    $subtotal = $quantity * $price;
                    $total += $subtotal;
                    
                    // Insert order item
                    $stmt = $pdo->prepare("INSERT INTO order_items 
                                          (order_id, product_id, quantity, price_per_unit) 
                                          VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $product_id, $quantity, $price]);
                    
                } catch (Exception $e) {
                    error_log("Error processing product ID {$product_ids[$i]}: " . $e->getMessage());
                    continue;
                }
            }
        }
        
        // Apply discount and calculate return amount
        $discount_amount = $total * ($discount_percent / 100);
        $final_total = $total - $discount_amount;
        $return_amount = $received_amount - $final_total;
        
        // Update order total and return amount
        $stmt = $pdo->prepare("UPDATE orders 
                              SET total = ?, return_amount = ?, order_date = NOW()
                              WHERE order_id = ?");
        $stmt->execute([$final_total, $return_amount, $order_id]);
        
        $pdo->commit();
        $message = "Order updated successfully! Additional ₹" . number_format($additional_amount, 2) . " received.";
        
        // Refresh order data
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT oi.*, p.product_name, p.price_per_unit as original_price 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.product_id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order #<?= htmlspecialchars($order_id) ?> | Grocery Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Your existing CSS styles here */
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-cart-check me-2"></i>Update Order #<?= htmlspecialchars($order_id) ?>
            </h1>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($order): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cart me-2"></i>Order Details</span>
                        <span class="badge bg-primary">Total Paid: ₹<?= number_format($order['received_amount'], 2) ?></span>
                    </div>
                    <div class="card-body">
                        <form method="post" id="order-form">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="customer_id" class="form-label">Customer</label>
                                    <select id="customer_id" name="customer_id" class="form-select" required>
                                        <option value="">Select a customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['customer_id'] ?>" 
                                                <?= $customer['customer_id'] == $order['customer_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="discount_percent" class="form-label">Discount (%)</label>
                                    <input type="number" id="discount_percent" name="discount_percent" 
                                           class="form-control" min="0" max="100" step="0.01" 
                                           value="<?= htmlspecialchars($order['discount_percent']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="received_amount" class="form-label">Additional Payment</label>
                                    <input type="number" id="received_amount" name="received_amount" 
                                           class="form-control" min="0" step="0.01" value="0" required>
                                    <small class="text-muted">Enter additional amount received</small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Order Items</h5>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                    <i class="bi bi-plus-circle me-1"></i> Add Item
                                </button>
                            </div>
                            
                            <div id="order-items">
                                <?php foreach ($order_items as $index => $item): ?>
                                <div class="order-item-row mb-3 p-3 border rounded">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label">Product</label>
                                            <select name="product_id[]" class="form-select product-select" required 
                                                    onchange="updatePrice(this, <?= $item['original_price'] ?>)">
                                                <option value="">Select a product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['product_id'] ?>" 
                                                        data-price="<?= $product['price_per_unit'] ?>"
                                                        <?= $product['product_id'] == $item['product_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($product['product_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" name="quantity[]" class="form-control" min="1" 
                                                   value="<?= htmlspecialchars($item['quantity']) ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Price per Unit</label>
                                            <input type="number" name="price_per_unit[]" class="form-control" 
                                                   min="0.01" step="0.01" 
                                                   value="<?= htmlspecialchars($item['price_per_unit']) ?>" required>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item" 
                                                    onclick="removeItem(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary me-md-2">
                                    <i class="bi bi-save me-1"></i> Update Order
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-receipt me-2"></i>Order Summary
                    </div>
                    <div class="card-body">
                        <div class="order-summary">
                            <h5 class="mb-4">Order #<?= htmlspecialchars($order_id) ?></h5>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal-display">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount (<span id="discount-percent"><?= htmlspecialchars($order['discount_percent']) ?></span>%):</span>
                                    <span id="discount-display">-₹0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Total:</strong>
                                    <strong class="total-display" id="total-display">₹0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Paid:</span>
                                    <span id="received-total">₹<?= number_format($order['received_amount'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Additional Payment:</span>
                                    <span id="received-display">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Balance:</strong>
                                    <strong id="return-display">₹0.00</strong>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Created:</span>
                                    <span><?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></span>
                                </div>
                                <?php if ($order['order_date']): ?>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Last Updated:</span>
                                    <span><?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add new order item
        function addItem() {
            const container = document.getElementById('order-items');
            const newItem = document.createElement('div');
            newItem.className = 'order-item-row mb-3 p-3 border rounded';
            newItem.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Product</label>
                        <select name="product_id[]" class="form-select product-select" required onchange="updatePrice(this)">
                            <option value="">Select a product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['product_id'] ?>" data-price="<?= $product['price_per_unit'] ?>">
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity[]" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price per Unit</label>
                        <input type="number" name="price_per_unit[]" class="form-control" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" 
                                onclick="removeItem(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Remove order item
        function removeItem(button) {
            const itemRow = button.closest('.order-item-row');
            itemRow.remove();
            calculateTotals();
        }
        
        // Update price when product is selected
        function updatePrice(select, originalPrice = null) {
            const row = select.closest('.order-item-row');
            const priceInput = row.querySelector('input[name="price_per_unit[]"]');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.price) {
                priceInput.value = originalPrice !== null ? originalPrice : selectedOption.dataset.price;
            }
            
            calculateTotals();
        }
        
        // Calculate order totals
        function calculateTotals() {
            let subtotal = 0;
            const productSelects = document.querySelectorAll('select[name="product_id[]"]');
            
            productSelects.forEach((select, index) => {
                if (select.value) {
                    const quantity = parseFloat(document.querySelectorAll('input[name="quantity[]"]')[index].value) || 0;
                    const price = parseFloat(document.querySelectorAll('input[name="price_per_unit[]"]')[index].value) || 0;
                    subtotal += (quantity * price);
                }
            });
            
            const discountPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
            const discountAmount = subtotal * (discountPercent / 100);
            const total = subtotal - discountAmount;
            const additionalAmount = parseFloat(document.getElementById('received_amount').value) || 0;
            const totalPaid = parseFloat(document.getElementById('received-total').textContent.replace('₹', '')) || 0;
            const newTotalPaid = totalPaid + additionalAmount;
            const balance = newTotalPaid - total;
            
            // Update display
            document.getElementById('subtotal-display').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('discount-percent').textContent = discountPercent;
            document.getElementById('discount-display').textContent = '-₹' + discountAmount.toFixed(2);
            document.getElementById('total-display').textContent = '₹' + total.toFixed(2);
            document.getElementById('received-display').textContent = '₹' + additionalAmount.toFixed(2);
            document.getElementById('return-display').textContent = '₹' + balance.toFixed(2);
            
            // Highlight balance
            const balanceDisplay = document.getElementById('return-display');
            if (balance < 0) {
                balanceDisplay.classList.add('text-danger');
                balanceDisplay.classList.remove('text-success');
                balanceDisplay.innerHTML = '(₹' + Math.abs(balance).toFixed(2) + ' due)';
            } else if (balance > 0) {
                balanceDisplay.classList.add('text-success');
                balanceDisplay.classList.remove('text-danger');
                balanceDisplay.innerHTML = '₹' + balance.toFixed(2) + ' credit';
            } else {
                balanceDisplay.classList.remove('text-danger', 'text-success');
                balanceDisplay.textContent = '₹0.00';
            }
        }
        
        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Set up change listeners for dynamic calculation
            document.getElementById('discount_percent').addEventListener('input', calculateTotals);
            document.getElementById('received_amount').addEventListener('input', calculateTotals);
            
            // Set up listeners for existing quantity and price inputs
            document.querySelectorAll('input[name="quantity[]"]').forEach(input => {
                input.addEventListener('input', calculateTotals);
            });
            
            document.querySelectorAll('input[name="price_per_unit[]"]').forEach(input => {
                input.addEventListener('input', calculateTotals);
            });
            
            // Initialize price for existing items
            document.querySelectorAll('select[name="product_id[]"]').forEach(select => {
                if (select.selectedIndex > 0) {
                    const row = select.closest('.order-item-row');
                    const originalPrice = row.querySelector('input[name="price_per_unit[]"]').value;
                    updatePrice(select, parseFloat(originalPrice));
                }
            });
            
            // Calculate initial totals
            calculateTotals();
        });
    </script>
</body>
</html>