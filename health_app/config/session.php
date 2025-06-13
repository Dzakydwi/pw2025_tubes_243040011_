<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'role') {
    header('location: login.php');
    exit();
}
?>