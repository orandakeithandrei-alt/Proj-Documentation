<?php
// Start session FIRST
session_start();

require_once 'database.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();
$orderId = $_GET['id'] ?? 0;

if (!$orderId) {
    header('Location: orders.php');
    exit();
}

$orderData = getOrderDetails($pdo, $orderId);

if (!$orderData['order']) {
    header('Location: orders.php');
    exit();
}

$order = $orderData['order'];
$items = $orderData['items'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - UM Canteen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-frame"><img src="assets/um-logo.png" alt="University of Mindanao logo"></div>
            <div class="university-name">UNIVERSITY OF MINDANAO DIGOS</div>
            <div class="system-name">Canteen Inventory & POS</div>
        </div>
        <nav class="menu">
            <button onclick="location.href='dashboard.php'">🏠 Dashboard</button>
            <button onclick="location.href='inventory.php'">📦 Inventory</button>
            <button onclick="location.href='add-item.php'">➕ Add Item</button>
            <button onclick="location.href='pos.php'">🛒 Point of Sale</button>
            <button class="active" onclick="location.href='orders.php'">📋 Orders</button>
        </nav>
        <button class="logout" onclick="location.href='logout.php'">🚪 Logout</button>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1>Order #<?= htmlspecialchars($order['order_number']) ?></h1><p>Order details</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <section class="content-box">
            <div class="order-info">
                <div><strong>Date:</strong> <?= date('F d, Y h:i A', strtotime($order['created_at'])) ?></div>
                <div><strong>Cashier:</strong> <?= htmlspecialchars($order['cashier'] ?? 'Unknown') ?></div>
                <div><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name'] ?? 'Walk-in') ?></div>
                <div><strong>Payment Method:</strong> <?= ucfirst($order['payment_method'] ?? 'cash') ?></div>
                <div><strong>Status:</strong> <?= ucfirst($order['status'] ?? 'completed') ?></div>
            </div>
            
            <table>
                <thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td>₱<?= number_format($item['price_at_time'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>₱<?= number_format($item['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty">No items found.</td></tr>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="text-align:right;"><strong>Total:</strong></td><td>₱<?= number_format($order['total_amount'], 2) ?></td></tr>
                    <?php if (($order['discount'] ?? 0) > 0): ?>
                        <tr><td colspan="3" style="text-align:right;"><strong>Discount:</strong></td><td>-₱<?= number_format($order['discount'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if (($order['tax'] ?? 0) > 0): ?>
                        <tr><td colspan="3" style="text-align:right;"><strong>Tax:</strong></td><td>₱<?= number_format($order['tax'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td colspan="3" style="text-align:right;"><strong>Grand Total:</strong></td><td><strong>₱<?= number_format($order['grand_total'], 2) ?></strong></td></tr>
                    <tr><td colspan="3" style="text-align:right;"><strong>Payment Received:</strong></td><td>₱<?= number_format($order['payment_received'] ?? 0, 2) ?></td></tr>
                    <tr><td colspan="3" style="text-align:right;"><strong>Change:</strong></td><td>₱<?= number_format($order['change_amount'] ?? 0, 2) ?></td></tr>
                </tfoot>
            </table>
            
            <div style="margin-top:20px;">
                <a href="orders.php" class="secondary-button">Back to Orders</a>
                <a href="pos.php" class="primary-button">New Order</a>
            </div>
        </section>
    </main>
</div>
</body>
</html>