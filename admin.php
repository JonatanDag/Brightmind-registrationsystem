<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$conn = getDB();

// Handle Delete
$toast = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $toast = $stmt->execute() ? "success|Student deleted successfully." : "error|Failed to delete.";
}

if (isset($_GET['logout'])) logout();

// Pagination & Search
$perPage    = 10;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;
$searchTerm = trim($_GET['search'] ?? '');

if ($searchTerm !== '') {
    $like = "%$searchTerm%";
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE full_name LIKE ? OR email LIKE ? OR course LIKE ?");
    $countStmt->bind_param("sss", $like, $like, $like);
    $countStmt->execute();
    $countStmt->bind_result($totalRows);
    $countStmt->fetch();
    $countStmt->close();
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE full_name LIKE ? OR email LIKE ? OR course LIKE ? ORDER BY reg_date DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $like, $like, $like, $perPage, $offset);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM registrations");
    $countStmt->execute();
    $countStmt->bind_result($totalRows);
    $countStmt->fetch();
    $countStmt->close();
    $stmt = $conn->prepare("SELECT * FROM registrations ORDER BY reg_date DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$result     = $stmt->get_result();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Stats
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM registrations")->fetch_assoc()['c'];
$todayStudents = $conn->query("SELECT COUNT(*) as c FROM registrations WHERE DATE(reg_date) = CURDATE()")->fetch_assoc()['c'];
$totalCourses  = $conn->query("SELECT COUNT(DISTINCT course) as c FROM registrations")->fetch_assoc()['c'];
$latestStudent = $conn->query("SELECT full_name FROM registrations ORDER BY reg_date DESC LIMIT 1")->fetch_assoc();

$dmType = $dmText = "";
if ($toast !== "") [$dmType, $dmText] = explode("|", $toast, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — <?php echo APP_NAME; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #0d0d1a;
      --sidebar: #11112b;
      --card: #161630;
      --card2: #1a1a38;
      --border: rgba(255,255,255,0.07);
      --primary: #6c63ff;
      --primary-glow: rgba(108,99,255,0.3);
      --secondary: #ff6584;
      --accent: #43e97b;
      --warning: #ffc107;
      --text: #f0f0ff;
      --text-muted: rgba(240,240,255,0.45);
      --text-dim: rgba(240,240,255,0.25);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── Top Nav ── */
    nav {
      background: var(--sidebar);
      border-bottom: 1px solid var(--border);
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(12px);
    }

    .nav-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .nav-logo {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }

    .nav-title {
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
    }

    .nav-subtitle {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 400;
    }

    .nav-right { display: flex; align-items: center; gap: 12px; }

    .nav-badge {
      background: rgba(108,99,255,0.15);
      border: 1px solid rgba(108,99,255,0.3);
      color: var(--primary);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .btn-nav {
      padding: 8px 16px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
    }

    .btn-reg {
      background: rgba(67,233,123,0.1);
      border: 1px solid rgba(67,233,123,0.25);
      color: #43e97b;
    }

    .btn-reg:hover { background: rgba(67,233,123,0.18); }

    .btn-out {
      background: rgba(255,101,132,0.1);
      border: 1px solid rgba(255,101,132,0.25);
      color: var(--secondary);
    }

    .btn-out:hover { background: rgba(255,101,132,0.18); }

    /* ── Page ── */
    .page { max-width: 1280px; margin: 0 auto; padding: 32px 24px; }

    /* ── Toast ── */
    .toast {
      position: fixed;
      top: 80px; right: 24px;
      padding: 14px 20px;
      border-radius: 14px;
      font-size: 14px;
      font-weight: 600;
      z-index: 999;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: toastIn 0.4s ease, toastOut 0.4s ease 3.6s forwards;
      backdrop-filter: blur(12px);
    }

    .toast.success {
      background: rgba(67,233,123,0.15);
      border: 1px solid rgba(67,233,123,0.3);
      color: #43e97b;
    }

    .toast.error {
      background: rgba(255,107,107,0.15);
      border: 1px solid rgba(255,107,107,0.3);
      color: #ff6b6b;
    }

    @keyframes toastIn  { from { opacity:0; transform: translateX(20px); } to { opacity:1; transform: translateX(0); } }
    @keyframes toastOut { from { opacity:1; } to { opacity:0; } }

    /* ── Stats ── */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }

    .stat {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 22px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      transition: transform 0.2s, box-shadow 0.2s;
      position: relative;
      overflow: hidden;
    }

    .stat::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      right: 0; height: 2px;
      border-radius: 2px 2px 0 0;
    }

    .stat:nth-child(1)::before { background: linear-gradient(90deg, var(--primary), #a78bfa); }
    .stat:nth-child(2)::before { background: linear-gradient(90deg, var(--accent), #38f9d7); }
    .stat:nth-child(3)::before { background: linear-gradient(90deg, var(--secondary), #f093fb); }
    .stat:nth-child(4)::before { background: linear-gradient(90deg, var(--warning), #ffd54f); }

    .stat:hover { transform: translateY(-3px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }

    .stat-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }

    .stat:nth-child(1) .stat-icon { background: rgba(108,99,255,0.15); }
    .stat:nth-child(2) .stat-icon { background: rgba(67,233,123,0.12); }
    .stat:nth-child(3) .stat-icon { background: rgba(255,101,132,0.12); }
    .stat:nth-child(4) .stat-icon { background: rgba(255,193,7,0.12); }

    .stat-num {
      font-size: 30px;
      font-weight: 800;
      color: var(--text);
      line-height: 1;
    }

    .stat-lbl {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 4px;
      font-weight: 500;
    }

    /* ── Panel ── */
    .panel {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      overflow: hidden;
    }

    .panel-top {
      padding: 20px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      border-bottom: 1px solid var(--border);
    }

    .panel-title {
      font-size: 16px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .panel-count {
      background: rgba(108,99,255,0.15);
      color: var(--primary);
      border-radius: 20px;
      padding: 2px 10px;
      font-size: 12px;
      font-weight: 700;
    }

    /* Search */
    .search-wrap {
      display: flex;
      gap: 0;
    }

    .search-wrap input {
      padding: 9px 16px;
      background: rgba(255,255,255,0.06);
      border: 1.5px solid var(--border);
      border-right: none;
      border-radius: 10px 0 0 10px;
      font-size: 13px;
      color: var(--text);
      outline: none;
      width: 220px;
      transition: border-color 0.2s;
      font-family: 'Inter', sans-serif;
    }

    .search-wrap input::placeholder { color: var(--text-dim); }
    .search-wrap input:focus { border-color: var(--primary); }

    .search-wrap button {
      padding: 9px 18px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 0 10px 10px 0;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      transition: background 0.2s;
      font-family: 'Inter', sans-serif;
    }

    .search-wrap button:hover { background: var(--primary-dark, #5a52e0); }

    /* Table */
    .table-wrap { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; font-size: 14px; }

    thead th {
      padding: 13px 18px;
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
      background: rgba(255,255,255,0.02);
    }

    tbody td {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    tbody tr:last-child td { border-bottom: none; }

    tbody tr { transition: background 0.15s; }
    tbody tr:hover { background: rgba(108,99,255,0.05); }

    .student-name { font-weight: 600; color: var(--text); }
    .student-email { color: var(--text-muted); font-size: 13px; }

    .avatar {
      width: 36px; height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 700;
      color: #fff;
      flex-shrink: 0;
    }

    .name-cell { display: flex; align-items: center; gap: 12px; }

    /* Badges */
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      text-transform: capitalize;
    }

    .badge-male   { background: rgba(100,149,237,0.15); color: #6495ed; border: 1px solid rgba(100,149,237,0.2); }
    .badge-female { background: rgba(255,105,180,0.15); color: #ff69b4; border: 1px solid rgba(255,105,180,0.2); }
    .badge-other  { background: rgba(167,139,250,0.15); color: #a78bfa; border: 1px solid rgba(167,139,250,0.2); }

    .course-tag {
      background: rgba(108,99,255,0.12);
      color: #a78bfa;
      border: 1px solid rgba(108,99,255,0.2);
      padding: 3px 10px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
    }

    /* Delete */
    .btn-delete {
      background: transparent;
      border: 1px solid rgba(255,107,107,0.25);
      color: rgba(255,107,107,0.6);
      padding: 6px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
    }

    .btn-delete:hover {
      background: rgba(255,107,107,0.12);
      border-color: rgba(255,107,107,0.5);
      color: #ff6b6b;
    }

    /* Empty */
    .empty {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-icon { font-size: 48px; margin-bottom: 12px; opacity: 0.5; }
    .empty p { font-size: 15px; }

    /* Pagination */
    .pagi {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      border-top: 1px solid var(--border);
      font-size: 13px;
      color: var(--text-muted);
      flex-wrap: wrap;
      gap: 10px;
    }

    .pagi-links { display: flex; gap: 6px; }

    .pagi-links a, .pagi-links span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 34px;
      height: 34px;
      padding: 0 10px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 500;
      border: 1px solid var(--border);
      color: var(--text-muted);
      transition: all 0.2s;
    }

    .pagi-links a:hover { background: rgba(255,255,255,0.06); color: var(--text); }
    .pagi-links span.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 12px var(--primary-glow); }
    .pagi-links span.off { opacity: 0.3; cursor: default; }

    @media (max-width: 700px) {
      .panel-top { flex-direction: column; align-items: flex-start; }
      .search-wrap input { width: 160px; }
      .stats { grid-template-columns: 1fr 1fr; }
      nav { padding: 0 16px; }
      .nav-badge { display: none; }
    }
  </style>
</head>
<body>

<?php if ($dmText !== ""): ?>
  <div class="toast <?php echo $dmType; ?>">
    <?php echo $dmType === 'success' ? '✅' : '❌'; ?>
    <?php echo htmlspecialchars($dmText); ?>
  </div>
<?php endif; ?>

<nav>
  <div class="nav-left">
    <div class="nav-logo">🎓</div>
    <div>
      <div class="nav-title"><?php echo htmlspecialchars(APP_NAME); ?></div>
      <div class="nav-subtitle">Admin Dashboard</div>
    </div>
  </div>
  <div class="nav-right">
    <span class="nav-badge">👑 Admin</span>
    <a href="index.php" class="btn-nav btn-reg">+ Registration</a>
    <a href="admin.php?logout=1" class="btn-nav btn-out">Sign Out</a>
  </div>
</nav>

<div class="page">

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-icon">👥</div>
      <div>
        <div class="stat-num"><?php echo $totalStudents; ?></div>
        <div class="stat-lbl">Total Students</div>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon">📅</div>
      <div>
        <div class="stat-num"><?php echo $todayStudents; ?></div>
        <div class="stat-lbl">Registered Today</div>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon">📚</div>
      <div>
        <div class="stat-num"><?php echo $totalCourses; ?></div>
        <div class="stat-lbl">Unique Courses</div>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon">⭐</div>
      <div>
        <div class="stat-num" style="font-size:16px;margin-top:4px"><?php echo $latestStudent ? htmlspecialchars($latestStudent['full_name']) : '—'; ?></div>
        <div class="stat-lbl">Latest Student</div>
      </div>
    </div>
  </div>

  <!-- Panel -->
  <div class="panel">
    <div class="panel-top">
      <div class="panel-title">
        All Students
        <span class="panel-count"><?php echo $totalRows; ?></span>
        <?php if ($searchTerm !== ""): ?>
          <span style="font-size:13px;color:var(--text-muted);font-weight:400">— "<?php echo htmlspecialchars($searchTerm); ?>"</span>
        <?php endif; ?>
      </div>
      <form class="search-wrap" method="get">
        <input type="text" name="search"
          placeholder="Search name, email, course…"
          value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">Search</button>
      </form>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Gender</th>
            <th>Course</th>
            <th>Phone</th>
            <th>Registered</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="7">
              <div class="empty">
                <div class="empty-icon">🔍</div>
                <p><?php echo $searchTerm !== "" ? "No students match your search." : "No students registered yet."; ?></p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php while ($row = $result->fetch_assoc()):
              $initials = strtoupper(substr($row['full_name'], 0, 1));
            ?>
            <tr>
              <td style="color:var(--text-dim);font-size:13px"><?php echo $row['id']; ?></td>
              <td>
                <div class="name-cell">
                  <div class="avatar"><?php echo $initials; ?></div>
                  <div>
                    <div class="student-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                    <div class="student-email"><?php echo htmlspecialchars($row['email']); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge badge-<?php echo htmlspecialchars($row['gender']); ?>">
                  <?php echo htmlspecialchars($row['gender']); ?>
                </span>
              </td>
              <td><span class="course-tag"><?php echo htmlspecialchars($row['course']); ?></span></td>
              <td style="color:var(--text-muted);font-size:13px">
                <?php echo $row['phone'] ? htmlspecialchars($row['phone']) : '<span style="color:var(--text-dim)">—</span>'; ?>
              </td>
              <td style="color:var(--text-muted);font-size:13px;white-space:nowrap">
                <?php echo date('M j, Y', strtotime($row['reg_date'])); ?>
              </td>
              <td>
                <form method="post" onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>? This cannot be undone.');">
                  <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                  <button type="submit" class="btn-delete">🗑 Delete</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagi">
      <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?> &nbsp;·&nbsp; <?php echo $totalRows; ?> students</span>
      <div class="pagi-links">
        <?php if ($page > 1): ?>
          <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($searchTerm); ?>">‹</a>
        <?php else: ?>
          <span class="off">‹</span>
        <?php endif; ?>

        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
          <?php if ($i === $page): ?>
            <span class="active"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($searchTerm); ?>">›</a>
        <?php else: ?>
          <span class="off">›</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>
</body>
</html>
