<?php
require_once __DIR__ . '/config.php';

function validateInput(array $post): array {
    $errors = [];
    $data   = [];

    $data['full_name'] = trim($post['full_name'] ?? '');
    $data['email']     = trim($post['email']     ?? '');
    $data['gender']    = trim($post['gender']    ?? '');
    $data['course']    = trim($post['course']    ?? '');
    $data['phone']     = trim($post['phone']     ?? '');

    if ($data['full_name'] === '' || strlen($data['full_name']) < 2)
        $errors['full_name'] = "Full name must be at least 2 characters.";
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = "Please enter a valid email address.";
    if (!in_array($data['gender'], ['male', 'female', 'other'], true))
        $errors['gender'] = "Please select a gender.";
    if ($data['course'] === '' || strlen($data['course']) < 2)
        $errors['course'] = "Course name must be at least 2 characters.";
    if ($data['phone'] !== '' && !preg_match('/^\+?[\d\s\-()]{7,20}$/', $data['phone']))
        $errors['phone'] = "Please enter a valid phone number.";

    return [$data, $errors];
}

$message = $msgType = "";
$formData = [];
$fieldErrors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    [$formData, $fieldErrors] = validateInput($_POST);

    if (empty($fieldErrors)) {
        $conn = getDB();
        $check = $conn->prepare("SELECT id FROM registrations WHERE email = ?");
        $check->bind_param("s", $formData['email']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $fieldErrors['email'] = "This email is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO registrations (full_name, email, gender, course, phone) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $formData['full_name'], $formData['email'], $formData['gender'], $formData['course'], $formData['phone']);
            if ($stmt->execute()) {
                $message  = "🎉 Welcome aboard! You're successfully registered.";
                $msgType  = "success";
                $formData = [];
            } else {
                $message = "Something went wrong. Please try again.";
                $msgType = "error";
            }
        }
    }
}

function old(string $key, array $data): string {
    return htmlspecialchars($data[$key] ?? '', ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Registration — <?php echo APP_NAME; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary: #6c63ff;
      --primary-dark: #5a52e0;
      --secondary: #ff6584;
      --accent: #43e97b;
      --dark: #0f0c29;
      --card-bg: rgba(255,255,255,0.07);
      --glass: rgba(255,255,255,0.12);
      --border: rgba(255,255,255,0.18);
      --text: #ffffff;
      --text-muted: rgba(255,255,255,0.6);
      --error: #ff6b6b;
      --success: #43e97b;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background blobs */
    body::before, body::after {
      content: '';
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.15;
      animation: float 8s ease-in-out infinite;
      pointer-events: none;
    }

    body::before {
      width: 500px; height: 500px;
      background: var(--primary);
      top: -100px; left: -100px;
    }

    body::after {
      width: 400px; height: 400px;
      background: var(--secondary);
      bottom: -100px; right: -100px;
      animation-delay: -4s;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-30px) scale(1.05); }
    }

    /* Header */
    .page-header {
      text-align: center;
      margin-bottom: 32px;
      position: relative;
      z-index: 1;
      animation: slideDown 0.7s ease;
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .logo-circle {
      width: 80px; height: 80px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin: 0 auto 16px;
      box-shadow: 0 8px 32px rgba(108,99,255,0.4);
    }

    .page-header h1 {
      font-size: 28px;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.5px;
    }

    .page-header p {
      color: var(--text-muted);
      font-size: 15px;
      margin-top: 6px;
      font-weight: 300;
    }

    /* Card */
    .card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 40px 36px;
      width: 100%;
      max-width: 500px;
      position: relative;
      z-index: 1;
      animation: slideUp 0.7s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Alert */
    .alert {
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 14px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
      animation: slideDown 0.4s ease;
    }

    .alert.success {
      background: rgba(67,233,123,0.15);
      border: 1px solid rgba(67,233,123,0.3);
      color: #43e97b;
    }

    .alert.error {
      background: rgba(255,107,107,0.15);
      border: 1px solid rgba(255,107,107,0.3);
      color: #ff6b6b;
    }

    /* Form */
    .form-group { margin-bottom: 20px; }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    label .req { color: var(--secondary); margin-left: 2px; }

    .input-wrap { position: relative; }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 16px;
      pointer-events: none;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    select {
      width: 100%;
      padding: 13px 14px 13px 42px;
      background: rgba(255,255,255,0.08);
      border: 1.5px solid rgba(255,255,255,0.12);
      border-radius: 12px;
      font-size: 15px;
      color: var(--text);
      outline: none;
      transition: all 0.3s;
      font-family: 'Inter', sans-serif;
    }

    input::placeholder { color: rgba(255,255,255,0.3); }

    select option { background: #302b63; color: #fff; }

    input:focus, select:focus {
      border-color: var(--primary);
      background: rgba(108,99,255,0.12);
      box-shadow: 0 0 0 4px rgba(108,99,255,0.15);
    }

    input.invalid, select.invalid {
      border-color: var(--error);
      box-shadow: 0 0 0 4px rgba(255,107,107,0.12);
    }

    .field-error {
      color: var(--error);
      font-size: 12px;
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* Submit button */
    .btn-submit {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: all 0.3s;
      letter-spacing: 0.3px;
      position: relative;
      overflow: hidden;
      font-family: 'Inter', sans-serif;
    }

    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(108,99,255,0.5); }
    .btn-submit:hover::after { opacity: 1; }
    .btn-submit:active { transform: translateY(0); }

    .privacy {
      text-align: center;
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .divider {
      height: 1px;
      background: var(--border);
      margin: 24px 0;
    }

    .admin-link {
      display: block;
      text-align: center;
      font-size: 13px;
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s;
    }

    .admin-link span {
      color: var(--primary);
      font-weight: 600;
    }

    .admin-link:hover span { text-decoration: underline; }

    /* Steps indicator */
    .steps {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 28px;
    }

    .step-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
    }

    .step-dot.active {
      background: var(--primary);
      width: 24px;
      border-radius: 4px;
    }

    @media (max-width: 520px) {
      .form-row { grid-template-columns: 1fr; }
      .card { padding: 28px 20px; }
    }
  </style>
</head>
<body>

  <div class="page-header">
    <div class="logo-circle">🎓</div>
    <h1><?php echo htmlspecialchars(APP_NAME); ?></h1>
    <p>Join thousands of students already learning with us</p>
  </div>

  <div class="card">
    <div class="steps">
      <div class="step-dot active"></div>
      <div class="step-dot"></div>
      <div class="step-dot"></div>
    </div>

    <?php if ($message !== ""): ?>
      <div class="alert <?php echo $msgType; ?>">
        <?php echo $msgType === 'success' ? '✅' : '❌'; ?>
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form action="" method="post" novalidate>

      <div class="form-group">
        <label>Full Name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="full_name" placeholder="John Doe"
            value="<?php echo old('full_name', $formData); ?>"
            class="<?php echo isset($fieldErrors['full_name']) ? 'invalid' : ''; ?>">
        </div>
        <?php if (isset($fieldErrors['full_name'])): ?>
          <div class="field-error">⚠ <?php echo htmlspecialchars($fieldErrors['full_name']); ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label>Email Address <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input type="email" name="email" placeholder="john@example.com"
            value="<?php echo old('email', $formData); ?>"
            class="<?php echo isset($fieldErrors['email']) ? 'invalid' : ''; ?>">
        </div>
        <?php if (isset($fieldErrors['email'])): ?>
          <div class="field-error">⚠ <?php echo htmlspecialchars($fieldErrors['email']); ?></div>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Gender <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">⚧</span>
            <select name="gender" class="<?php echo isset($fieldErrors['gender']) ? 'invalid' : ''; ?>">
              <option value="">-- Select --</option>
              <option value="male"   <?php echo old('gender',$formData)==='male'   ? 'selected':''; ?>>Male</option>
              <option value="female" <?php echo old('gender',$formData)==='female' ? 'selected':''; ?>>Female</option>
              <option value="other"  <?php echo old('gender',$formData)==='other'  ? 'selected':''; ?>>Other</option>
            </select>
          </div>
          <?php if (isset($fieldErrors['gender'])): ?>
            <div class="field-error">⚠ <?php echo htmlspecialchars($fieldErrors['gender']); ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label>Phone <span style="color:rgba(255,255,255,0.3);font-weight:400">(optional)</span></label>
          <div class="input-wrap">
            <span class="input-icon">📱</span>
            <input type="tel" name="phone" placeholder="+1 555 0100"
              value="<?php echo old('phone', $formData); ?>"
              class="<?php echo isset($fieldErrors['phone']) ? 'invalid' : ''; ?>">
          </div>
          <?php if (isset($fieldErrors['phone'])): ?>
            <div class="field-error">⚠ <?php echo htmlspecialchars($fieldErrors['phone']); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Course <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">📚</span>
          <input type="text" name="course" placeholder="e.g. Web Development"
            value="<?php echo old('course', $formData); ?>"
            class="<?php echo isset($fieldErrors['course']) ? 'invalid' : ''; ?>">
        </div>
        <?php if (isset($fieldErrors['course'])): ?>
          <div class="field-error">⚠ <?php echo htmlspecialchars($fieldErrors['course']); ?></div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-submit">Register Now →</button>

      <p class="privacy">🔒 Your data is encrypted and stored securely</p>
    </form>

    <div class="divider"></div>
    <a href="login.php" class="admin-link">Admin? <span>Sign in to the portal →</span></a>
  </div>

</body>
</html>
