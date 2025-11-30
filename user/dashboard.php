<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Redirect to merged home/dashboard page
header('Location: ../index.php' . (isset($_GET['booked']) ? '?booked=1' : ''));
exit;
?>
