<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { 
  header('Location: login.html'); 
  exit; 
}
include __DIR__ . '/../backend/db.php';
$user_id = $_SESSION['user_id']; 
$name = $_SESSION['name'];

// Fetch all requests for this student
$stmt = $conn->prepare("SELECT r.*, t.name AS technician_name 
  FROM maintenance_requests r 
  LEFT JOIN users t ON r.technician_id = t.user_id 
  WHERE r.student_id = ? 
  ORDER BY r.created_at DESC");

if ($stmt) {
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  die("SQL Error in request fetch: " . $conn->error);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student - Smart Maintenance</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { background: #fafaff; }
    .hero-card {
      background: linear-gradient(135deg, #ffffff, #f7f6ff);
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 20px 30px;
      margin: 25px 0;
    }
    .hero-card h4 { color: #444; font-size: 1.3rem; margin-bottom: 10px; }
    form.form label { display: block; margin-top: 12px; color: #555; }
    form.form input, form.form select, form.form textarea {
      width: 100%; padding: 8px 10px; margin-top: 4px; border-radius: 8px;
      border: 1px solid #ccc; font-size: 0.95rem;
    }
    .btn.primary {
      background-color: #6c63ff; border: none; color: white;
      padding: 10px 18px; border-radius: 8px; font-weight: 600;
      cursor: pointer; transition: 0.2s;
    }
    .btn.primary:hover { background-color: #5848e8; }
    .request-card {
      background: #fff; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      padding: 16px 20px; margin-bottom: 18px; transition: 0.2s ease;
    }
    .request-header {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;
    }
    .status { display: inline-block; padding: 4px 10px; border-radius: 8px;
      font-size: 0.85rem; color: #fff; font-weight: 600;
    }
    .status.Pending { background-color: #ffb84d; }
    .status.InProgress { background-color: #6c63ff; }
    .status.Completed { background-color: #4caf50; }
    .status.Rejected { background-color: #e74c3c; }
    .review { margin-top: 12px; padding-top: 10px; border-top: 1px solid #eee; }
    .star-rating { direction: rtl; display: inline-flex; }
    .star-rating input { display: none; }
    .star-rating label {
      font-size: 22px; color: #ccc; cursor: pointer; transition: color 0.2s;
    }
    .star-rating input:checked ~ label,
    .star-rating label:hover, .star-rating label:hover ~ label {
      color: #f8b400;
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
        <a href="../backend/logout.php">🔒 Logout</a>
      </nav>
    </div>
  </header>

  <header class="bar">
    <h3>Student Dashboard — <?= htmlspecialchars($name) ?></h3>
  </header>

  <main class="container">
    <section class="hero-card">
      <h4>Submit a new request</h4>
      <form id="reqForm" class="form">
        <label>Title<input name="title" required></label>
        <label>Technician Type
          <select name="tech_type" required>
            <option value="Electrical">Electrical</option>
            <option value="Plumbing">Plumbing</option>
            <option value="Carpentry">Carpentry</option>
            <option value="Cleaning">Cleaning</option>
          </select>
        </label>
        <label>Location<input name="location" required placeholder="Building / Room"></label>
        <label>Contact<input name="contact" placeholder="phone or email"></label>
        <label>Description<textarea name="description" required></textarea></label>
        <div class="form-row"><button class="btn primary" type="submit">Submit Request</button></div>
      </form>
    </section>

    <section>
      <h4>Your requests</h4>
      <?php if (empty($requests)): ?>
        <p>No requests yet.</p>
      <?php else: foreach ($requests as $r): ?>
        <div class="request-card">
          <div class="request-header">
            <h3>🧾 #<?= $r['request_id'] ?> — <?= htmlspecialchars($r['title']) ?></h3>
            <span class="status <?= htmlspecialchars($r['status']) ?>">
              <?= htmlspecialchars($r['status']) ?>
            </span>
          </div>
          <div class="request-body">
            <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($r['tech_type']) ?> | 
               <strong>Location:</strong> <?= htmlspecialchars($r['location']) ?></p>
            <p><strong>Technician:</strong> <?= htmlspecialchars($r['technician_name'] ?? 'Not assigned') ?></p>
            <p><strong>Created:</strong> <?= htmlspecialchars($r['created_at']) ?></p>

            <?php if ($r['status'] === 'Completed'): ?>
              <?php if (!empty($r['start_time']) && !empty($r['end_time'])): ?>
                <p><strong>Time taken:</strong>
                  <?php
                    $start = strtotime($r['start_time']);
                    $end = strtotime($r['end_time']);
                    $mins = round(($end - $start) / 60);
                    echo $mins > 0 ? "$mins minutes" : "N/A";
                  ?>
                </p>
              <?php endif; ?>

              <?php
                $reviewExists = false;
                $qr = $conn->prepare("SHOW TABLES LIKE 'reviews'");
                $qr->execute();
                $result = $qr->get_result();
                if ($result->num_rows > 0) {
                  $check = $conn->prepare("SELECT COUNT(*) AS c FROM reviews WHERE request_id=?");
                  $check->bind_param('i', $r['request_id']);
                  $check->execute();
                  $c = $check->get_result()->fetch_assoc();
                  $reviewExists = ($c['c'] > 0);
                }
              ?>

              <?php if (!$reviewExists): ?>
                <div class="review">
                  <h5>Give Review</h5>
                  <form onsubmit="submitReview(event, <?= $r['request_id'] ?>, <?= intval($r['technician_id'] ?: 0) ?>)">
                    <div class="star-rating">
                      <input type="radio" id="star5-<?= $r['request_id'] ?>" name="rating-<?= $r['request_id'] ?>" value="5"><label for="star5-<?= $r['request_id'] ?>">★</label>
                      <input type="radio" id="star4-<?= $r['request_id'] ?>" name="rating-<?= $r['request_id'] ?>" value="4"><label for="star4-<?= $r['request_id'] ?>">★</label>
                      <input type="radio" id="star3-<?= $r['request_id'] ?>" name="rating-<?= $r['request_id'] ?>" value="3"><label for="star3-<?= $r['request_id'] ?>">★</label>
                      <input type="radio" id="star2-<?= $r['request_id'] ?>" name="rating-<?= $r['request_id'] ?>" value="2"><label for="star2-<?= $r['request_id'] ?>">★</label>
                      <input type="radio" id="star1-<?= $r['request_id'] ?>" name="rating-<?= $r['request_id'] ?>" value="1" required><label for="star1-<?= $r['request_id'] ?>">★</label>
                    </div>
                    <label>Feedback<textarea id="feedback-<?= $r['request_id'] ?>" required></textarea></label>
                    <div class="form-row"><button class="btn primary" type="submit">Submit Review</button></div>
                  </form>
                </div>
              <?php else: ?>
                <p><em>Already reviewed.</em></p>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </section>
  </main>

  <script>
  document.getElementById('reqForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch('../backend/submit_request.php', { method: 'POST', body: form });
    const j = await res.json();
    if (j.success) {
      alert('✅ Request submitted successfully!');
      location.reload();
    } else {
      alert('❌ Error: ' + (j.error || 'unknown'));
    }
  });

  function getSelectedRating(requestId){
    const radios = document.getElementsByName('rating-' + requestId);
    for(const r of radios) if (r.checked) return r.value;
    return null;
  }

  async function submitReview(e, requestId, technicianId){
    e.preventDefault();
    const rating = getSelectedRating(requestId);
    const feedback = document.getElementById('feedback-' + requestId).value;
    if (!rating) { alert('Please select a rating.'); return; }

    const payload = new URLSearchParams();
    payload.append('request_id', requestId);
    payload.append('technician_id', technicianId);
    payload.append('rating', rating);
    payload.append('feedback', feedback);

    const res = await fetch('../backend/review_submit.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: payload
    });
    const j = await res.json();
    if (j.success) {
      alert('⭐ Review submitted successfully!');
      location.reload();
    } else {
      alert('Error: ' + (j.error || 'unknown'));
    }
  }
  </script>
</body>
</html>
