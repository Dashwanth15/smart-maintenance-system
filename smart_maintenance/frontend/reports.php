<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
  header('Location: login.html'); 
  exit; 
}
include __DIR__ . '/../backend/db.php';

$stmt = $conn->prepare("
  SELECT r.request_id, r.title, s.name AS student_name, t.name AS technician_name, 
         rv.rating, rv.feedback, rv.created_at 
  FROM reviews rv
  JOIN maintenance_requests r ON rv.request_id = r.request_id
  JOIN users s ON r.student_id = s.user_id
  LEFT JOIN users t ON r.technician_id = t.user_id
  ORDER BY rv.created_at DESC
");
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reports - Smart Maintenance</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background-color: #fafaff;
      font-family: 'Poppins', sans-serif;
      color: #333;
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

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 12px;
    }

    th, td {
      padding: 12px 16px;
      text-align: left;
    }

    th {
      background: #6c63ff;
      color: #fff;
      border-radius: 6px 6px 0 0;
    }

    tr {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      transition: transform 0.2s;
    }

    tr:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }

    .rating {
      color: #ffb400;
      font-size: 1.1rem;
    }

    .feedback {
      font-style: italic;
      color: #444;
    }

    footer {
      margin-top: 40px;
      text-align: center;
      color: #666;
      font-size: 0.9rem;
    }

    .btn {
      background: #6c63ff;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn:hover {
      background: #5848e8;
      transform: translateY(-2px);
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
      <h2>📊 Maintenance Reports & Reviews</h2>
      <p>All student feedback and technician performance</p>
    </div>

    <?php if(count($reviews)===0): ?>
      <p style="text-align:center;">No reviews submitted yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Title</th>
            <th>Student</th>
            <th>Technician</th>
            <th>Rating</th>
            <th>Feedback</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($reviews as $rev): ?>
          <tr>
            <td>#<?= $rev['request_id'] ?></td>
            <td><?= htmlspecialchars($rev['title']) ?></td>
            <td><?= htmlspecialchars($rev['student_name']) ?></td>
            <td><?= htmlspecialchars($rev['technician_name'] ?? 'N/A') ?></td>
            <td class="rating"><?= str_repeat("⭐", intval($rev['rating'])) ?></td>
            <td class="feedback">“<?= htmlspecialchars($rev['feedback']) ?>”</td>
            <td><?= htmlspecialchars($rev['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <footer>
    <p>Smart Maintenance System © 2025 | Made with 💜</p>
  </footer>

</body>
</html>
