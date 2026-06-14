<?php
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) { header("Location: admin.php"); exit; }

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } elseif (login($username, $password)) {
        header("Location: admin.php"); exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?php echo APP_NAME; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      width: 600px; height: 600px;
      background: radial-gradient(circle, rgba(108,99,255,0.3) 0%, transparent 70%);
      top: -200px; left: -200px;
      pointer-events: none;
    }

    body::after {
      content: '';
      position: fixed;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(255,101,132,0.2) 0%, transparent 70%);
      bottom: -100px; right: -100px;
      pointer-events: none;
    }

    .login-wrap {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 420px;
      animation: fadeUp 0.6s ease;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .brand {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-logo {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #6c63ff, #ff6584);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin: 0 auto 16px;
      box-shadow: 0 8px 32px rgba(108,99,255,0.45);
    }

    .brand h1 {
      font-size: 24px;
      font-weight: 800;
      color: #fff;
    }

    .brand p {
      color: rgba(255,255,255,0.5);
      font-size: 14px;
      margin-top: 4px;
    }

    .card {
      background: rgba(255,255,255,0.07);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 24px;
      padding: 36px 32px;
    }

    .error-box {
      background: rgba(255,107,107,0.15);
      border: 1px solid rgba(255,107,107,0.3);
      color: #ff6b6b;
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 14px;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
    }

    .form-group { margin-bottom: 20px; }

    label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      color: rgba(255,255,255,0.5);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .input-wrap { position: relative; }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 16px;
      pointer-events: none;
    }

    input {
      width: 100%;
      padding: 13px 14px 13px 44px;
      background: rgba(255,255,255,0.08);
      border: 1.5px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      font-size: 15px;
      color: #fff;
      outline: none;
      transition: all 0.3s;
      font-family: 'Inter', sans-serif;
    }

    input::placeholder { color: rgba(255,255,255,0.25); }

    input:focus {
      border-color: #6c63ff;
      background: rgba(108,99,255,0.12);
      box-shadow: 0 0 0 4px rgba(108,99,255,0.18);
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #6c63ff, #5a52e0);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      font-family: 'Inter', sans-serif;
      margin-top: 4px;
      letter-spacing: 0.3px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(108,99,255,0.55);
    }

    .btn-login:active { transform: translateY(0); }

    .back {
      display: block;
      text-align: center;
      margin-top: 20px;
      font-size: 13px;
      color: rgba(255,255,255,0.4);
      text-decoration: none;
      transition: color 0.2s;
    }

    .back:hover { color: rgba(255,255,255,0.8); }

    .divider {
      height: 1px;
      background: rgba(255,255,255,0.1);
      margin: 22px 0;
    }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="brand">
      <div class="brand-logo">🎓</div>
      <h1><?php echo htmlspecialchars(APP_NAME); ?></h1>
      <p>Admin Portal — Sign in to continue</p>
    </div>

    <div class="card">
      <?php if ($error !== ""): ?>
        <div class="error-box">⚠️ <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form action="" method="post" novalidate>
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" name="username" placeholder="Enter your username"
              value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
              autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔑</span>
            <input type="password" name="password" placeholder="Enter your password"
              autocomplete="current-password">
          </div>
        </div>

        <button type="submit" class="btn-login">Sign In →</button>
      </form>

      <div class="divider"></div>
      <a href="index.php" class="back">← Back to Registration</a>
    </div>
  </div>
</body>
</html>
