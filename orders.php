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
$orders = getOrders($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - UM Canteen</title>
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
            <div><h1>Orders</h1><p>View all processed orders</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <section class="content-box">
            <h2>Order History</h2>
            <table>
                <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Cashier</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= $order['item_count'] ?? 0 ?></td>
                            <td>₱<?= number_format($order['grand_total'], 2) ?></td>
                            <td><?= ucfirst($order['payment_method'] ?? 'cash') ?></td>
                            <td><?= htmlspecialchars($order['cashier'] ?? 'Unknown') ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                            <td><a href="order-details.php?id=<?= $order['order_id'] ?>" class="edit-button">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty">No orders found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>