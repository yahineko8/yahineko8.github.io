<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $db = getDB();

        // Check if username/email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
            $stmt->bind_param("sss", $username, $email, $hash);

            if ($stmt->execute()) {
                $success = 'Account created successfully! Please login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - <?php echo APP_NAME; ?></title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .auth-card {
      background: rgba(255,255,255,0.95);
      padding: 40px;
      border-radius: 24px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.3);
      width: 100%;
      max-width: 500px;
    }
    .auth-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .auth-header h1 {
      color: #1e3c72;
      font-size: 2rem;
      margin-bottom: 10px;
    }
    .form-group {
      margin-bottom: 20px;
      position: relative;
    }
    .form-group input {
      width: 100%;
      padding: 14px 14px 14px 48px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.3s;
      background: #fafafa;
    }
    .form-group input:focus {
      border-color: #2a5298;
      background: white;
      outline: none;
    }
    .form-group i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
    }
    .error-message {
      background: #ffebee;
      color: #c62828;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .success-message {
      background: #e8f5e9;
      color: #2e7d32;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .btn-auth {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    .btn-auth:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(42,82,152,0.3);
    }
    .auth-footer {
      text-align: center;
      margin-top: 25px;
      color: #666;
    }
    .auth-footer a {
      color: #2a5298;
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-header">
      <h1><i class="fas fa-user-plus"></i> Create Account</h1>
      <p>Join <?php echo APP_NAME; ?> today</p>
    </div>

    <?php if ($error): ?>
    <div class="error-message">
      <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success-message">
      <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <input type="text" name="username" placeholder="Username" required minlength="3">
        <i class="fas fa-user"></i>
      </div>

      <div class="form-group">
        <input type="email" name="email" placeholder="Email Address" required>
        <i class="fas fa-envelope"></i>
      </div>

      <div class="form-group">
        <input type="password" name="password" placeholder="Password" required minlength="6">
        <i class="fas fa-lock"></i>
      </div>

      <div class="form-group">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <i class="fas fa-lock"></i>
      </div>

      <button type="submit" class="btn-auth">
        <i class="fas fa-user-plus"></i> Create Account
      </button>
    </form>

    <div class="auth-footer">
      <p>Already have an account? <a href="login.php">Sign In</a></p>
      <p style="margin-top: 10px;"><a href="index.php">← Continue as Guest</a></p>
    </div>
  </div>
</body>
</html>