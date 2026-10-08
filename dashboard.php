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
$stats = getDashboardStats($pdo);
$lowStock = getLowStockItems($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UM Canteen</title>
    <link rel="stylesheet" href="style.css">
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
        <button class="active" onclick="location.href='dashboard.php'">🏠 Dashboard</button>
        <button onclick="location.href='inventory.php'">📦 Inventory</button>
        <button onclick="location.href='add-item.php'">➕ Add Item</button>
        <button onclick="location.href='inventory.php?filter=low'">⚠️ Low Stock</button>
        <button onclick="location.href='pos.php'">🛒 POS</button>
        <button onclick="location.href='transactions.php'">📜 Transaction History</button>
    </nav>
        <button class="logout" onclick="location.href='logout.php'">🚪 Logout</button>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1>Dashboard</h1><p>University of Mindanao Digos Canteen Inventory Management</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <section class="cards">
            <div class="card">
                <div class="card-icon">📦</div>
                <div><p>Total Items</p><h2><?= $stats['total_items'] ?? 0 ?></h2></div>
            </div>
            <div class="card">
                <div class="card-icon">📊</div>
                <div><p>Total Stock</p><h2><?= $stats['total_stock'] ?? 0 ?></h2></div>
            </div>
            <div class="card warning-card">
                <div class="card-icon">⚠️</div>
                <div><p>Low Stock</p><h2><?= $stats['low_stock'] ?? 0 ?></h2></div>
            </div>
            <div class="card">
                <div class="card-icon">💰</div>
                <div><p>Inventory Value</p><h2>₱<?= number_format($stats['inventory_value'] ?? 0, 2) ?></h2></div>
            </div>
            <div class="card">
                <div class="card-icon">💳</div>
                <div><p>Today's Sales</p><h2>₱<?= number_format($stats['today_sales'] ?? 0, 2) ?></h2></div>
            </div>
        </section>

        <?php if (!empty($lowStock)): ?>
        <section class="content-box warning-box">
            <h3>⚠️ Low Stock Alerts</h3>
            <table>
                <thead><tr><th>Item</th><th>Category</th><th>Stock</th><th>Reorder Level</th></tr></thead>
                <tbody>
                <?php foreach ($lowStock as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                        <td class="text-danger"><?= $item['stock'] ?></td>
                        <td><?= $item['reorder_level'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>

        <section class="content-box">
            <div class="section-header">
                <div><h2>Recent Inventory</h2><p>Latest canteen products</p></div>
                <a class="primary-button" href="inventory.php">View Inventory</a>
            </div>
            <table>
                <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                <tbody>
                <?php 
                $recentItems = $stats['recent_items'] ?? [];
                if (!empty($recentItems)): 
                ?>
                    <?php foreach ($recentItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                            <td>₱<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['stock'] ?></td>
                            <td>
                                <span class="status <?= $item['status'] ?? 'available' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $item['status'] ?? 'Available')) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty">No inventory items yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
        
        <section class="content-box">
            <div class="section-header">
                <div><h2>Recent Orders</h2><p>Latest transactions</p></div>
                <a class="primary-button" href="orders.php">View All Orders</a>
            </div>
            <table>
                <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Cashier</th><th>Date</th></tr></thead>
                <tbody>
                <?php if (!empty($stats['recent_orders'])): ?>
                    <?php foreach ($stats['recent_orders'] as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['customer_name'] ?? 'Walk-in') ?></td>
                            <td>₱<?= number_format($order['grand_total'], 2) ?></td>
                            <td><?= htmlspecialchars($order['cashier'] ?? 'Unknown') ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty">No orders yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<script src="app.js"></script>
</body>
</html>