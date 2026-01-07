<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') { 
  header('Location: login.html'); 
  exit; 
}
include __DIR__ . '/../backend/db.php';
$user_id = $_SESSION['user_id']; 
$name = $_SESSION['name'];

// Fetch assigned requests
$stmt = $conn->prepare("SELECT r.*, s.name AS student_name 
  FROM maintenance_requests r 
  LEFT JOIN users s ON r.student_id = s.user_id 
  WHERE r.technician_id = ? 
  ORDER BY r.created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Technician - Smart Maintenance</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background: #fafaff;
    }

    .dashboard-header {
      background: linear-gradient(135deg, #6c63ff, #9c6bff);
      color: #fff;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .request-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      padding: 18px 22px;
      margin-bottom: 18px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .request-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }

    .request-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .request-header h3 {
      margin: 0;
      font-size: 1.1rem;
      color: #333;
    }

    .status {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.85rem;
      color: #fff;
      font-weight: 600;
    }

    .status.Pending { background-color: #ffb84d; }
    .status.InProgress { background-color: #6c63ff; }
    .status.Completed { background-color: #4caf50; }

    .request-body p {
      margin: 6px 0;
      color: #555;
      font-size: 0.95rem;
    }

    .action-btn {
      margin-top: 10px;
      background-color: #6c63ff;
      border: none;
      color: #fff;
      padding: 8px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.2s;
    }

    .action-btn:hover {
      background-color: #5848e8;
    }

    footer {
      margin-top: 40px;
      text-align: center;
      color: #666;
    }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <a href="index.html"><img id="site-logo" src="../assets/images/logo.svg" alt="Smart Maintenance logo"></a>
      </div>
      <nav class="nav">
        <a href="index.html">🏠 Home</a>
        <a href="student.php">👩‍🎓 Student</a>
        <a href="admin.php">🧑‍💼 Admin</a>
        <a href="technician.php">🛠️ Technician</a>
        <a href="logout.php">🔒 Logout</a>
      </nav>
    </div>
  </header>

  <div class="container">
    <div class="dashboard-header">
      <h2>Welcome, <?= htmlspecialchars($name) ?> 🛠️</h2>
      <p>Here are your assigned maintenance requests</p>
    </div>

    <?php if (count($requests) === 0): ?>
      <p>No assigned requests yet.</p>
    <?php else: foreach ($requests as $r): ?>
      <div class="request-card">
        <div class="request-header">
          <h3>🔧 #<?= $r['request_id'] ?> — <?= htmlspecialchars($r['title']) ?></h3>
          <span class="status <?= htmlspecialchars($r['status']) ?>">
            <?= htmlspecialchars($r['status']) ?>
          </span>
        </div>
        <div class="request-body">
          <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
          <p><strong>Type:</strong> <?= htmlspecialchars($r['tech_type']) ?> |
             <strong>Location:</strong> <?= htmlspecialchars($r['location']) ?></p>
          <p><strong>Student:</strong> <?= htmlspecialchars($r['student_name']) ?></p>
          <p><strong>Created:</strong> <?= htmlspecialchars($r['created_at']) ?></p>

                    <?php
            $st = strtolower(trim($r['status'] ?? ''));
            if ($st === 'pending' || $st === 'assigned' || $st === 'not assigned' || $st === 'not_assigned'): ?>
            <button class="action-btn status-btn" data-action="start" data-id="<?= $r['request_id'] ?>">Start Work</button>
          <?php elseif ($st === 'in progress' || $st === 'inprogress'): ?>
            <p><strong>⏳ Started:</strong> <?= htmlspecialchars($r['start_time']) ?></p>
            <button class="action-btn status-btn" data-action="complete" data-id="<?= $r['request_id'] ?>">Mark as Completed</button>
          <?php elseif ($st === 'completed'): ?>
            <p><strong>✅ Started:</strong> <?= htmlspecialchars($r['start_time']) ?></p>
            <p><strong>✅ Completed:</strong> <?= htmlspecialchars($r['end_time']) ?></p>
            <p><strong>🕒 Time Taken:</strong> <?= htmlspecialchars($r['total_time'] ?? $r['time_taken'] ?? '') ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <footer>
    <p>Smart Maintenance System © 2025 | Made with 💜</p>
  </footer>

  <script>
  async function startWork(requestId) {
    if(!confirm('Start working on this request?')) return;
    const formData = new URLSearchParams();
    formData.append('request_id', requestId);
    const res = await fetch('../backend/start_work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });
    const j = await res.json();
    if (j.success) { alert('Work started successfully.'); location.reload(); }
    else alert('Error: ' + (j.error || 'unknown'));
  }

  async function markCompleted(requestId) {
    if(!confirm('Mark this request as completed?')) return;
    const formData = new URLSearchParams();
    formData.append('request_id', requestId);
    const res = await fetch('../backend/mark_completed.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });
    const j = await res.json();
    if (j.success) { alert('Request marked as completed!'); location.reload(); }
    else alert('Error: ' + (j.error || 'unknown'));
  }
  </script>


<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.status-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var action = btn.getAttribute('data-action');
      var requestId = btn.getAttribute('data-id');
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch('../backend/update_status.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'request_id=' + encodeURIComponent(requestId) + '&action=' + encodeURIComponent(action)
      }).then(r=>r.json()).then(function(json){
        if (json.success) {
          // simple reload to reflect UI, but we can update in-place
          location.reload();
        } else {
          alert('Error: ' + (json.error||'unknown'));
          btn.disabled = false;
          btn.textContent = action==='start'?'Start Work':'Mark as Completed';
        }
      }).catch(function(err){
        alert('Request failed: ' + err);
        btn.disabled = false;
        btn.textContent = action==='start'?'Start Work':'Mark as Completed';
      });
    });
  });
});
</script>
</body>
</html>
