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
$items = getAllItems($pdo);
$categories = getCategories($pdo);
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['search'] ?? '');
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Apply filters
if ($filter === 'low') {
    $items = array_filter($items, function($item) {
        return $item['stock'] <= $item['reorder_level'];
    });
}

if ($search) {
    $items = array_filter($items, function($item) use ($search) {
        return stripos($item['name'], $search) !== false || 
               stripos($item['category_name'] ?? '', $search) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - UM Canteen</title>
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
            <button class="active" onclick="location.href='inventory.php'">📦 Inventory</button>
            <button onclick="location.href='add-item.php'">➕ Add Item</button>
            <button onclick="location.href='inventory.php?filter=low'">⚠️ Low Stock</button>
            <button onclick="location.href='pos.php'">🛒 POS</button>
        </nav>
        <button class="logout" onclick="location.href='logout.php'">🚪 Logout</button>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1>Inventory</h1><p>Manage canteen products and stock</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="content-box">
            <div class="inventory-header">
                <div><h2>Inventory List</h2><p>Manage all available canteen products.</p></div>
                <a class="primary-button" href="add-item.php">+ Add New Item</a>
            </div>
            <form method="GET" action="" class="search-form">
                <input class="search" id="searchInput" name="search" type="text" placeholder="🔍 Search item or category..." value="<?= htmlspecialchars($search) ?>">
                <?php if ($filter === 'low'): ?>
                    <input type="hidden" name="filter" value="low">
                <?php endif; ?>
            </form>
            <table>
                <thead><tr><th>ID</th><th>Item Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= str_pad($item['item_id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                            <td>₱<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['stock'] ?></td>
                            <td>
                                <span class="status <?= $item['status'] ?? 'available' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $item['status'] ?? 'Available')) ?>
                                </span>
                            </td>
                            <td>
                                <a href="add-item.php?edit=<?= $item['item_id'] ?>" class="edit-button">Edit</a>
                                <a href="delete-item.php?id=<?= $item['item_id'] ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty">No items found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            this.closest('form').submit();
        });
    }
});
</script>
</body>
</html>