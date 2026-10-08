<?php
require_once 'database.php';
require_once 'functions.php';
requireLogin();

$pdo = getDBConnection();
$items = getAllItems($pdo);
$categories = getCategories($pdo);

// Get only available items
$availableItems = array_filter($items, function($item) {
    return $item['stock'] > 0;
});

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_order'])) {
    $cart = json_decode($_POST['cart_data'] ?? '[]', true);
    
    if (empty($cart)) {
        $error = 'Cart is empty. Please add items to the cart.';
    } else {
        $orderData = [
            'items' => $cart,
            'customer_name' => trim($_POST['customer_name'] ?? ''),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'discount' => floatval($_POST['discount'] ?? 0),
            'tax' => floatval($_POST['tax'] ?? 0),
            'grand_total' => floatval($_POST['grand_total'] ?? 0),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'payment_received' => floatval($_POST['payment_received'] ?? 0),
            'change_amount' => floatval($_POST['change_amount'] ?? 0),
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        try {
            $result = createOrder($pdo, $orderData, $_SESSION['user_id']);
            header('Location: order-details.php?id=' . $result['order_id']);
            exit();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - UM Canteen</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="pos.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-frame"><img src="assets/um-logo.png" alt="University of Mindanao logo"></div>
            <div class="university-name">UNIVERSITY OF MINDANAO DIGOS</div>
            <div class="system-name">Canteen Inventory System</div>
        </div>
        <nav class="menu">
            <button onclick="location.href='dashboard.php'">🏠 Dashboard</button>
            <button onclick="location.href='inventory.php'">📦 Inventory</button>
            <button onclick="location.href='add-item.php'">➕ Add Item</button>
            <button onclick="location.href='inventory.php?filter=low'">⚠️ Low Stock</button>
            <button class="active" onclick="location.href='pos.php'">🛒 POS</button>
        </nav>
        <button class="logout" onclick="location.href='logout.php'">🚪 Logout</button>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1>Point of Sale</h1><p>Process customer orders</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="pos-container">
            <!-- Product Selection -->
            <div class="pos-products">
                <div class="pos-header">
                    <h3>Products</h3>
                    <input type="text" id="productSearch" placeholder="Search products..." class="search">
                </div>
                <div class="category-filters">
                    <button class="filter-btn active" data-category="all">All</button>
                    <?php foreach ($categories as $category): ?>
                        <button class="filter-btn" data-category="<?= $category['category_id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="product-grid" id="productGrid">
                    <?php foreach ($availableItems as $item): ?>
                        <div class="product-item" 
                             data-id="<?= $item['item_id'] ?>" 
                             data-category="<?= $item['category_id'] ?>" 
                             data-price="<?= $item['price'] ?>" 
                             data-stock="<?= $item['stock'] ?>" 
                             data-name="<?= htmlspecialchars($item['name']) ?>">
                            <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="product-price">₱<?= number_format($item['price'], 2) ?></div>
                            <div class="product-stock">Stock: <?= $item['stock'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart -->
            <div class="pos-cart">
                <div class="pos-header">
                    <h3>Cart</h3>
                    <button class="secondary-button" onclick="clearCart()">Clear</button>
                </div>
                <div class="cart-items" id="cartItems">
                    <p class="empty">No items in cart</p>
                </div>
                <div class="cart-summary">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="cartTotal">₱0.00</span>
                    </div>
                    <form method="POST" action="" id="posForm">
                        <input type="hidden" name="cart_data" id="cartData">
                        <input type="hidden" name="total_amount" id="totalAmount">
                        <input type="hidden" name="grand_total" id="grandTotal">
                        
                        <div class="form-group">
                            <label class="form-label" for="customer_name">Customer Name</label>
                            <input type="text" name="customer_name" id="customer_name" placeholder="Walk-in" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="payment_method">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-control">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="gcash">GCash</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="paymentReceived">Payment Received</label>
                            <input type="number" name="payment_received" id="paymentReceived" step="0.01" class="form-control" oninput="calculateChange()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="changeAmount">Change</label>
                            <input type="number" name="change_amount" id="changeAmount" step="0.01" class="form-control" readonly>
                        </div>
                        
                        <input type="hidden" name="discount" value="0">
                        <input type="hidden" name="tax" value="0">
                        <input type="hidden" name="notes" value="">
                        
                        <button type="submit" name="process_order" class="primary-button" id="checkoutBtn" disabled>Process Order</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="pos.js"></script>
</body>
</html>