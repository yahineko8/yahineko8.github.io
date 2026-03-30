<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --primary: #1e3c72;
      --secondary: #2a5298;
      --accent: #667eea;
      --success: #4caf50;
      --warning: #ff9800;
      --danger: #f44336;
      --light: #f5f7fa;
      --dark: #333;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      color: var(--dark);
    }
    .navbar {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      padding: 1rem 2rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .nav-container {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      color: white;
      font-size: 1.5rem;
      font-weight: 700;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .nav-links {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }
    .nav-links a {
      color: rgba(255,255,255,0.9);
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      transition: all 0.3s;
      font-size: 0.9rem;
    }
    .nav-links a:hover, .nav-links a.active {
      color: white;
      background: rgba(255,255,255,0.1);
    }
    .user-menu {
      display: flex;
      align-items: center;
      gap: 1rem;
      color: white;
      margin-left: 1rem;
    }
    .btn-logout {
      background: rgba(255,255,255,0.2);
      border: none;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .main-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }
    .flash-message {
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .flash-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4caf50; }
    .flash-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
    .flash-warning { background: #fff3e0; color: #ef6c00; border-left: 4px solid #ff9800; }
    .card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .card-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 1.5rem;
    }
    .card-body { padding: 1.5rem; }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(30,60,114,0.3); }
    .btn-secondary {
      background: white;
      color: #666;
      border: 2px solid #e0e0e0;
    }
    .btn-secondary:hover { border-color: var(--secondary); color: var(--secondary); }
    .btn-danger { background: var(--danger); color: white; }
    .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #333;
    }
    .form-control {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s;
    }
    .form-control:focus {
      border-color: var(--secondary);
      outline: none;
      box-shadow: 0 0 0 4px rgba(42,82,152,0.1);
    }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    .table th { background: #f8f9fa; font-weight: 600; color: var(--primary); }
    .table tr:hover { background: #f8f9fa; }
    .badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .badge-success { background: #e8f5e9; color: #388e3c; }
    .badge-warning { background: #fff3e0; color: #f57c00; }
    .badge-danger { background: #ffebee; color: #c62828; }
    .badge-info { background: #e3f2fd; color: #1976d2; }
    .text-center { text-align: center; }
    .mt-4 { margin-top: 2rem; }
    .mb-4 { margin-bottom: 2rem; }
    .grid { display: grid; gap: 1.5rem; }
    .grid-2 { grid-template-columns: repeat(2, 1fr); }
    .grid-3 { grid-template-columns: repeat(3, 1fr); }
    .grid-4 { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 768px) {
      .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
      .nav-links { display: none; }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="logo">
        <i class="fas fa-cogs"></i>
        <span><?php echo APP_NAME; ?></span>
      </a>
      <div class="nav-links">
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
          <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="components.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'components.php' ? 'active' : ''; ?>">
          <i class="fas fa-list"></i> Components
        </a>
        <a href="rfid_scan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rfid_scan.php' ? 'active' : ''; ?>">
          <i class="fas fa-wifi"></i> RFID Scan
        </a>
        <a href="register_component.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'register_component.php' ? 'active' : ''; ?>">
          <i class="fas fa-plus-circle"></i> Register
        </a>
        <a href="payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
          <i class="fas fa-credit-card"></i> Payment
        </a>
        <?php if (isLoggedIn()): ?>
        <div class="user-menu">
          <span><?php echo $_SESSION['user']['username']; ?></span>
          <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
        <?php else: ?>
        <a href="login.php" class="btn-logout"><i class="fas fa-sign-in-alt"></i> Login</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="main-container">
    <?php if ($flash = getFlash()): ?>
    <div class="flash-message flash-<?php echo $flash['type']; ?>">
      <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'error' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
      <?php echo $flash['message']; ?>
    </div>
    <?php endif; ?>
