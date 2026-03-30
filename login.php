<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];

                // Update last login
                $db->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

                setFlash('success', 'Welcome back, ' . $user['username'] . '!');
                redirect('index.php');
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?php echo APP_NAME; ?></title>
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
      backdrop-filter: blur(10px);
      padding: 50px;
      border-radius: 24px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.3);
      width: 100%;
      max-width: 450px;
    }
    .auth-header {
      text-align: center;
      margin-bottom: 40px;
    }
    .auth-header h1 {
      color: #1e3c72;
      font-size: 2.2rem;
      margin-bottom: 10px;
    }
    .auth-header p { color: #666; }
    .form-group {
      margin-bottom: 25px;
      position: relative;
    }
    .form-group input {
      width: 100%;
      padding: 15px 15px 15px 50px;
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
      left: 18px;
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
      display: flex;
      align-items: center;
      gap: 8px;
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
      margin-top: 30px;
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
      <h1><i class="fas fa-cogs"></i> <?php echo APP_NAME; ?></h1>
      <p>Sign in to manage motor components</p>
    </div>

    <?php if ($error): ?>
    <div class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <input type="text" name="username" placeholder="Username or Email" required 
               value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>">
        <i class="fas fa-user"></i>
      </div>

      <div class="form-group">
        <input type="password" name="password" placeholder="Password" required>
        <i class="fas fa-lock"></i>
      </div>

      <button type="submit" class="btn-auth">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="auth-footer">
      <p>Don't have an account? <a href="register_user.php">Create Account</a></p>
      <p style="margin-top: 15px;"><a href="index.php">← Continue as Guest</a></p>
    </div>
  </div>
</body>
</html>