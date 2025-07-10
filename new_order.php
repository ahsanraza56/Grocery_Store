<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_product_price') {
        $product_id = intval($_GET['product_id']);
        $result = $conn->query("SELECT price_per_unit FROM products WHERE product_id = $product_id");
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['price' => $row['price_per_unit']]);
        } else {
            echo json_encode(['price' => 0]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'search_products') {
        $search = $_GET['search'];
        $result = $conn->query("SELECT product_id, product_name, price_per_unit FROM products 
                               WHERE product_name LIKE '%$search%' 
                               ORDER BY product_name ASC LIMIT 10");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['product_id'],
                'text' => $row['product_name'] . ' (Rs' . number_format($row['price_per_unit'], 2) . ')'
            ];
        }
        echo json_encode(['results' => $products]);
        exit;
    }
    
    if ($_GET['action'] === 'add_customer') {
        $customer_name = trim($_POST['customer_name']);
        if (!empty($customer_name)) {
            // Check if customer already exists
            $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE customer_name = ?");
            $stmt->bind_param("s", $customer_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Customer exists, return existing ID
                $row = $result->fetch_assoc();
                echo json_encode(['success' => true, 'customer_id' => $row['customer_id'], 'message' => 'Customer already exists']);
            } else {
                // Create new customer
                $stmt = $conn->prepare("INSERT INTO customers (customer_name, email, phone, address) VALUES (?, '', '', '')");
                $stmt->bind_param("s", $customer_name);
                if ($stmt->execute()) {
                    $customer_id = $stmt->insert_id;
                    echo json_encode(['success' => true, 'customer_id' => $customer_id, 'message' => 'New customer added']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error adding customer']);
                }
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer name required']);
        }
        exit;
    }
}

// Fetch customers for dropdown
$customers = $conn->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $customer_id = $_POST['customer_id'];
    $items = json_decode($_POST['items'], true);
    $discount_percent = floatval($_POST['discount_percent']);
    $received_amount = floatval($_POST['received_amount']);
    $total = floatval($_POST['total']);
    $return_amount = floatval($_POST['return_amount']);

    $conn->begin_transaction();

    try {
        // Insert into orders
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, total, discount_percent, received_amount, return_amount, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("idddd", $customer_id, $total, $discount_percent, $received_amount, $return_amount);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $qty = intval($item['quantity']);
            $unit_price = floatval($item['price']);

            // Decrease product quantity
            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $qty, $product_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $product_id, $qty, $unit_price);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        header("Location: bill.php?order_id=$order_id");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New Order - Grocery Store</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .select2-container--default .select2-selection--single {
      height: 38px;
      padding: 5px;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <h3>New Order</h3>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back</a>

  <form method="POST" id="order-form">
    
    <!-- Customer Selection Section -->
    <div class="card mb-3">
      <div class="card-header">Customer Information</div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <label>Select Existing Customer</label>
            <select name="customer_id" class="form-select">
              <option value="">-- Select Customer --</option>
              <?php while ($cust = $customers->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($cust['customer_id']) ?>"><?= htmlspecialchars($cust['customer_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label>Or Add New Customer</label>
            <input type="text" id="customer_name_input" class="form-control" placeholder="Enter customer name">
          </div>
          <div class="col-md-2">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-primary d-block" onclick="addCustomer()">Add Customer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Selection Section -->
    <div class="card mb-3">
      <div class="card-header">Add Products</div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <label>Search Product</label>
            <select id="product" class="form-select product-search" style="width: 100%">
              <option value="">Search product...</option>
            </select>
          </div>
          <div class="col-md-3">
            <label>Quantity</label>
            <input type="number" id="quantity" class="form-control" placeholder="Quantity" min="1">
          </div>
          <div class="col-md-3">
            <label>Unit Price</label>
            <input type="number" step="0.01" id="price" class="form-control" placeholder="Unit Price" readonly>
          </div>
          <div class="col-md-2">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-success d-block" onclick="addToCart()">Add to Cart</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart Table -->
    <div class="card mb-3">
      <div class="card-header">Order Items</div>
      <div class="card-body">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Subtotal</th>
              <th>Remove</th>
            </tr>
          </thead>
          <tbody id="cart-body"></tbody>
        </table>
      </div>
    </div>

    <!-- Order Summary -->
    <div class="card mb-3">
      <div class="card-header">Order Summary</div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <label>Total</label>
            <input type="number" step="0.01" name="total" id="total" class="form-control" readonly>
          </div>
          <div class="col-md-3">
            <label>Discount (%)</label>
            <input type="number" name="discount_percent" id="discount_percent" class="form-control" value="0" oninput="calculateReturn()">
          </div>
          <div class="col-md-3">
            <label>Received Amount</label>
            <input type="number" step="0.01" name="received_amount" id="received_amount" class="form-control" oninput="calculateReturn()">
          </div>
          <div class="col-md-3">
            <label>Return</label>
            <input type="number" step="0.01" name="return_amount" id="return_amount" class="form-control" readonly>
          </div>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-primary btn-lg" onclick="submitOrder()">Place Order</button>
    <input type="hidden" name="place_order" value="1">
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let cart = [];

    // Initialize Select2 for product search
    $(document).ready(function() {
        $('.product-search').select2({
            placeholder: 'Search product...',
            ajax: {
                url: '?action=search_products',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results.map(item => ({
                            id: item.id,
                            text: item.text
                        }))
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        // When product is selected, fetch its price
        $('.product-search').on('select2:select', function(e) {
            const productId = e.params.data.id;
            fetchProductPrice(productId);
        });
    });

    // Fetch product price
    function fetchProductPrice(productId) {
        if (productId) {
            fetch(`?action=get_product_price&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("price").value = data.price;
                })
                .catch(error => console.error('Error:', error));
        } else {
            document.getElementById("price").value = '';
        }
    }

    // Add customer automatically when name is entered
    function addCustomer() {
        const customerName = document.getElementById("customer_name_input").value.trim();
        if (!customerName) {
            alert("Please enter customer name");
            return;
        }

        const formData = new FormData();
        formData.append('customer_name', customerName);

        fetch('?action=add_customer', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add new option to select dropdown
                const select = document.querySelector('select[name="customer_id"]');
                const option = new Option(customerName, data.customer_id, true, true);
                select.appendChild(option);
                
                // Clear the input field
                document.getElementById("customer_name_input").value = '';
                
                // Show success message
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding customer');
        });
    }

    function addToCart() {
        const productSelect = document.getElementById("product");
        const productId = productSelect.value;
        const productText = productSelect.selectedOptions[0].text.split(' (')[0]; // Remove price from text
        const price = parseFloat(document.getElementById("price").value);
        const qty = parseInt(document.getElementById("quantity").value);

        if (!productId || qty <= 0 || isNaN(price)) return alert("Fill all fields correctly.");

        cart.push({ product_id: productId, name: productText, quantity: qty, price: price });

        renderCart();
        
        // Clear form fields
        $('.product-search').val(null).trigger('change');
        document.getElementById("quantity").value = '';
        document.getElementById("price").value = '';
    }

    function renderCart() {
        let tbody = document.getElementById("cart-body");
        tbody.innerHTML = '';
        let total = 0;

        cart.forEach((item, index) => {
            const subtotal = item.quantity * item.price;
            total += subtotal;

            tbody.innerHTML += `<tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${item.price.toFixed(2)}</td>
                <td>${subtotal.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger" onclick="removeItem(${index})">X</button></td>
            </tr>`;
        });

        document.getElementById("total").value = total.toFixed(2);
        calculateReturn();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function calculateReturn() {
        const total = parseFloat(document.getElementById("total").value);
        const discount = parseFloat(document.getElementById("discount_percent").value || 0);
        const received = parseFloat(document.getElementById("received_amount").value || 0);
        const discountedTotal = total - (total * discount / 100);
        const returnAmount = received - discountedTotal;
        document.getElementById("return_amount").value = returnAmount.toFixed(2);
    }

    function submitOrder() {
        if (cart.length === 0) return alert("Add at least one product!");

        const customerSelect = document.querySelector('select[name="customer_id"]');
        if (!customerSelect.value) return alert("Please select a customer!");

        const form = document.getElementById("order-form");
        let existing = form.querySelector('input[name="items"]');
        if (existing) existing.remove();

        const cartField = document.createElement("input");
        cartField.type = "hidden";
        cartField.name = "items";
        cartField.value = JSON.stringify(cart);
        form.appendChild(cartField);
        form.submit();
    }
</script>
</body>
</html>