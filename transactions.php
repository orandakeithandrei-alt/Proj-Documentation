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

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$sql = "
    SELECT o.*, u.full_name as cashier,
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count,
    (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.order_id) as total_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE 1=1
";
$params = [];

// Apply date filters
if ($filter === 'today') {
    $sql .= " AND DATE(o.created_at) = CURDATE()";
} elseif ($filter === 'week') {
    $sql .= " AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'month') {
    $sql .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
}

if ($search) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR u.full_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($dateFrom) {
    $sql .= " AND DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Summary statistics
$totalTransactions = count($transactions);
$totalRevenue = 0;
$totalItemsSold = 0;
foreach ($transactions as $t) {
    $totalRevenue += $t['grand_total'];
    $totalItemsSold += $t['total_items'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - UM Canteen</title>
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
            <button onclick="location.href='dashboard.php'">🏠 Dashboard</button>
            <button onclick="location.href='inventory.php'">📦 Inventory</button>
            <button onclick="location.href='add-item.php'">➕ Add Item</button>
            <button onclick="location.href='inventory.php?filter=low'">⚠️ Low Stock</button>
            <button onclick="location.href='pos.php'">🛒 POS</button>
            <button class="active" onclick="location.href='transactions.php'">📜 Transaction History</button>
        </nav>
        <button class="logout" onclick="location.href='logout.php'">🚪 Logout</button>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1>Transaction History</h1><p>View all completed transactions</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <!-- Summary Cards -->
        <section class="cards">
            <div class="card">
                <div class="card-icon">📜</div>
                <div><p>Total Transactions</p><h2><?= $totalTransactions ?></h2></div>
            </div>
            <div class="card">
                <div class="card-icon">💰</div>
                <div><p>Total Revenue</p><h2>₱<?= number_format($totalRevenue, 2) ?></h2></div>
            </div>
            <div class="card">
                <div class="card-icon">📦</div>
                <div><p>Items Sold</p><h2><?= $totalItemsSold ?></h2></div>
            </div>
        </section>

        <!-- Filters -->
        <section class="content-box">
            <div class="inventory-header">
                <div><h2>Transaction List</h2><p>Filter and search transactions</p></div>
            </div>

            <!-- Quick filter buttons -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px;">
                <a href="transactions.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                <a href="transactions.php?filter=today" class="filter-btn <?= $filter === 'today' ? 'active' : '' ?>">Today</a>
                <a href="transactions.php?filter=week" class="filter-btn <?= $filter === 'week' ? 'active' : '' ?>">This Week</a>
                <a href="transactions.php?filter=month" class="filter-btn <?= $filter === 'month' ? 'active' : '' ?>">This Month</a>
            </div>

            <!-- Search & date range -->
            <form method="GET" action="" class="search-form" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                <?php if ($filter !== 'all'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <?php endif; ?>
                <div>
                    <label class="form-label" style="margin-top:0;">Search</label>
                    <input class="search" name="search" type="text" placeholder="🔍 Order #, customer, cashier..." value="<?= htmlspecialchars($search) ?>" style="margin-bottom:0;">
                </div>
                <div>
                    <label class="form-label" style="margin-top:0;">From</label>
                    <input class="form-control" name="date_from" type="date" value="<?= htmlspecialchars($dateFrom) ?>" style="width:auto;">
                </div>
                <div>
                    <label class="form-label" style="margin-top:0;">To</label>
                    <input class="form-control" name="date_to" type="date" value="<?= htmlspecialchars($dateTo) ?>" style="width:auto;">
                </div>
                <button class="primary-button" type="submit">Apply</button>
                <a class="secondary-button" href="transactions.php">Reset</a>
            </form>
        </section>

        <!-- Transaction table -->
        <section class="content-box">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Payment</th>
                        <th>Cashier</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($t['order_number']) ?></strong></td>
                            <td><?= date('M d, Y h:i A', strtotime($t['created_at'])) ?></td>
                            <td><?= htmlspecialchars($t['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= $t['total_items'] ?> item(s)</td>
                            <td><?= ucfirst($t['payment_method'] ?? 'cash') ?></td>
                            <td><?= htmlspecialchars($t['cashier'] ?? 'Unknown') ?></td>
                            <td><strong>₱<?= number_format($t['grand_total'], 2) ?></strong></td>
                            <td>
                                <a href="order-details.php?id=<?= $t['order_id'] ?>" class="edit-button">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty">No transactions found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>