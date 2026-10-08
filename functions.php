<?php
require_once 'database.php';

// Category functions
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// Item functions
function getAllItems($pdo) {
    $stmt = $pdo->query("
        SELECT i.*, c.name as category_name,
        CASE 
            WHEN i.stock <= 0 THEN 'out_of_stock'
            WHEN i.stock <= i.reorder_level THEN 'low_stock'
            ELSE 'available'
        END as status
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        ORDER BY i.name
    ");
    return $stmt->fetchAll();
}

function getItemById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name 
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE i.item_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addItem($pdo, $data, $userId) {
    $stmt = $pdo->prepare("
        INSERT INTO items (name, category_id, price, stock, description, reorder_level, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $data['name'],
        $data['category_id'],
        $data['price'],
        $data['stock'],
        $data['description'],
        $data['reorder_level'],
        $userId
    ]);
    return $pdo->lastInsertId();
}

function updateItem($pdo, $id, $data, $userId) {
    $stmt = $pdo->prepare("
        UPDATE items 
        SET name = ?, category_id = ?, price = ?, stock = ?, description = ?, reorder_level = ?, updated_by = ?, updated_at = NOW()
        WHERE item_id = ?
    ");
    $stmt->execute([
        $data['name'],
        $data['category_id'],
        $data['price'],
        $data['stock'],
        $data['description'],
        $data['reorder_level'],
        $userId,
        $id
    ]);
}

function deleteItem($pdo, $id, $userId) {
    // Check if item exists
    $stmt = $pdo->prepare("SELECT * FROM items WHERE item_id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception("Item not found.");
    }
    
    // Soft delete or hard delete? Let's do hard delete for simplicity
    $stmt = $pdo->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->execute([$id]);
}

function getLowStockItems($pdo) {
    $stmt = $pdo->query("
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE i.stock <= i.reorder_level
        ORDER BY i.stock ASC
    ");
    return $stmt->fetchAll();
}

function getDashboardStats($pdo) {
    $stats = [];
    
    // Total items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
    $stats['total_items'] = $stmt->fetchColumn();
    
    // Total stock
    $stmt = $pdo->query("SELECT SUM(stock) as total FROM items");
    $stats['total_stock'] = $stmt->fetchColumn() ?? 0;
    
    // Low stock count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM items WHERE stock <= reorder_level");
    $stats['low_stock'] = $stmt->fetchColumn();
    
    // Inventory value
    $stmt = $pdo->query("SELECT SUM(price * stock) as value FROM items");
    $stats['inventory_value'] = $stmt->fetchColumn() ?? 0;
    
    // Today's sales
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(grand_total), 0) as total 
        FROM orders 
        WHERE DATE(created_at) = CURDATE() AND status = 'completed'
    ");
    $stats['today_sales'] = $stmt->fetchColumn();
    
    // Recent items (for dashboard)
    $stmt = $pdo->query("
        SELECT i.*, c.name as category_name,
        CASE 
            WHEN i.stock <= 0 THEN 'out_of_stock'
            WHEN i.stock <= i.reorder_level THEN 'low_stock'
            ELSE 'available'
        END as status
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        ORDER BY i.created_at DESC
        LIMIT 5
    ");
    $stats['recent_items'] = $stmt->fetchAll();
    
    // Recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name as cashier 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.status = 'completed'
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['recent_orders'] = $stmt->fetchAll();
    
    return $stats;
}

// Order functions
function getOrders($pdo) {
    $stmt = $pdo->query("
        SELECT o.*, u.full_name as cashier,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
    ");
    return $stmt->fetchAll();
}

function getOrderDetails($pdo, $orderId) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name as cashier 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        return ['order' => null, 'items' => []];
    }
    
    $stmt = $pdo->prepare("
        SELECT oi.*, i.name as item_name
        FROM order_items oi
        JOIN items i ON oi.item_id = i.item_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    return ['order' => $order, 'items' => $items];
}

function createOrder($pdo, $orderData, $userId) {
    try {
        $pdo->beginTransaction();
        
        // Generate order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_number, user_id, customer_name, total_amount, discount, tax, 
                grand_total, payment_method, payment_received, change_amount, 
                notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())
        ");
        
        $stmt->execute([
            $orderNumber,
            $userId,
            $orderData['customer_name'],
            $orderData['total_amount'],
            $orderData['discount'],
            $orderData['tax'],
            $orderData['grand_total'],
            $orderData['payment_method'],
            $orderData['payment_received'],
            $orderData['change_amount'],
            $orderData['notes']
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items and update stock
        foreach ($orderData['items'] as $item) {
            // Insert order item
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, item_id, quantity, price_at_time, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $item['item_id'],
                $item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity']
            ]);
            
            // Update stock
            $stmt = $pdo->prepare("UPDATE items SET stock = stock - ? WHERE item_id = ? AND stock >= ?");
            $stmt->execute([$item['quantity'], $item['item_id'], $item['quantity']]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Not enough stock for item: " . $item['name']);
            }
        }
        
        $pdo->commit();
        
        return ['order_id' => $orderId, 'order_number' => $orderNumber];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>