<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Location: ' . (empty($_SESSION['user_id']) ? 'login.php' : 'dashboard.php'));
exit;
