<?php
require_once 'database.php';
require_once 'functions.php';
requireLogin();

$pdo = getDBConnection();
$categories = getCategories($pdo);
$item = null;
$isEdit = false;
$editId = $_GET['edit'] ?? null;

if ($editId) {
    $isEdit = true;
    $item = getItemById($pdo, $editId);
    if (!$item) {
        header('Location: inventory.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'category_id' => $_POST['category_id'] ?? '',
        'price' => floatval($_POST['price'] ?? 0),
        'stock' => intval($_POST['stock'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'reorder_level' => intval($_POST['reorder_level'] ?? 10)
    ];
    
    // Validation
    if (empty($data['name'])) {
        $error = 'Please enter an item name.';
    } elseif (empty($data['category_id'])) {
        $error = 'Please select a category.';
    } elseif ($data['price'] < 0) {
        $error = 'Please enter a valid price.';
    } elseif ($data['stock'] < 0 || !is_int($data['stock'])) {
        $error = 'Stock must be a whole number of 0 or more.';
    } else {
        $userId = $_SESSION['user_id'];
        
        try {
            if ($isEdit) {
                updateItem($pdo, $editId, $data, $userId);
                $message = 'Item updated successfully!';
            } else {
                addItem($pdo, $data, $userId);
                $message = 'Item added successfully!';
            }
            header('Location: inventory.php?success=' . urlencode($message));
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
    <title><?= $isEdit ? 'Edit' : 'Add' ?> Item - UM Canteen</title>
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
            <button class="active" onclick="location.href='add-item.php'">➕ Add Item</button>
            <button onclick="location.href='inventory.php?filter=low'">⚠️ Low Stock</button>
            <button onclick="location.href='pos.php'">🛒 POS</button>
        </nav>
        <button class="logout" onclick="location.href='logout.php'">🚪 Logout</button>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1><?= $isEdit ? 'Edit' : 'Add' ?> Item</h1><p><?= $isEdit ? 'Update' : 'Add or edit' ?> a canteen product.</p></div>
            <div class="admin">👤 <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
        </header>

        <section class="form-box">
            <h2><?= $isEdit ? 'Edit Item' : 'Add New Item' ?></h2>
            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Item Name</label>
                        <input class="form-control" id="name" name="name" type="text" placeholder="Example: Rice" required value="<?= htmlspecialchars($item['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="category_id">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>" <?= (isset($item) && $item['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="price">Price (₱)</label>
                        <input class="form-control" id="price" name="price" type="number" min="0" step="0.01" required value="<?= htmlspecialchars($item['price'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="stock">Stock Quantity</label>
                        <input class="form-control" id="stock" name="stock" type="number" min="0" step="1" required value="<?= htmlspecialchars($item['stock'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="reorder_level">Reorder Level</label>
                    <input class="form-control" id="reorder_level" name="reorder_level" type="number" min="0" step="1" value="<?= htmlspecialchars($item['reorder_level'] ?? 10) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" placeholder="Enter item description"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                </div>
                <div class="form-buttons">
                    <a class="secondary-button" href="inventory.php">Cancel</a>
                    <button class="primary-button" type="submit"><?= $isEdit ? 'Update Item' : 'Save Item' ?></button>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>