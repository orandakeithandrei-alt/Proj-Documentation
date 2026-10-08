<?php
require_once 'database.php';
require_once 'functions.php';
requireLogin();

$pdo = getDBConnection();
$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        deleteItem($pdo, $id, $_SESSION['user_id']);
        header('Location: inventory.php?success=' . urlencode('Item deleted successfully'));
    } catch (Exception $e) {
        header('Location: inventory.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: inventory.php');
}
exit();
?>