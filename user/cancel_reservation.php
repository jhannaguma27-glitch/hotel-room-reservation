<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_POST) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND user_id = ? AND status = 'confirmed'");
$stmt->execute([$_POST['reservation_id'], $_SESSION['user_id']]);

header('Location: ../index.php');
exit;
?>
