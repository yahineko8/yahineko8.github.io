<?php
require_once 'config.php';
session_destroy();
setFlash('success', 'You have been logged out successfully');
redirect('login.php');
?>