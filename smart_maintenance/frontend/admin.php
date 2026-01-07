<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: login.html');
  exit;
}
include __DIR__ . '/../backend/db.php';
$name = $_SESSION['name'];

$stmt = $conn->prepare("SELECT r.*, s.name AS student_name, t.name AS technician_name 
                        FROM maintenance_requests r 
                        LEFT JOIN users s ON r.student_id = s.user_id 
                        LEFT JOIN users t ON r.technician_id = t.user_id 
                        ORDER BY r.created_at DESC");
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Smart Maintenance</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
  font-family: "Poppins", sans-serif;
  background: #f4f6fb;
  margin: 0;
  color: #333;
}

header.site-header {
  background: linear-gradient(90deg, #5a5bf3, #8f94fb);
  color: #fff;
  padding: 12px 0;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.header-inner {
  width: 92%;
  margin: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand img { height: 45px; }

.nav a {
  color: #fff;
  margin: 0 12px;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  transition: opacity .2s;
}
.nav a:hover { opacity: 0.85; }

.container {
  width: 90%;
  max-width: 1100px;
  margin: 30px auto;
}

h2 {
  font-size: 22px;
  margin-bottom: 20px;
}

.dashboard-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 25px;
}

.dashboard-buttons a {
  background: #6c63ff;
  color: #fff;
  padding: 10px 22px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  transition: 0.2s;
}
.dashboard-buttons a:hover {
  background: #5650e5;
  transform: translateY(-2px);
}

#requestsSection {
  background: #fff;
  border-radius: 12px;
  padding: 22px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.request-card {
  background: #fafafa;
  border: 1px solid #e1e1e1;
  border-radius: 10px;
  padding: 15px 18px;
  margin-bottom: 14px;
  box-shadow: 0 1px 5px rgba(0,0,0,0.04);
  transition: 0.2s;
}
.request-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.request-title {
  font-size: 15px;
  font-weight: 600;
  color: #222;
  margin-bottom: 6px;
}

.request-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  font-size: 14px;
  line-height: 1.4;
  color: #555;
  margin-bottom: 6px;
}

.request-row span {
  flex: 1;
  min-width: 180px;
}

.request-row strong { color: #333; }

form {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-start;
  padding-top: 6px;
  border-top: 1px dashed #ddd;
  margin-top: 8px;
}

select {
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 13px;
}

button.btn-assign {
  background: #5a5bf3;
  color: #fff;
  border: none;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  transition: 0.2s;
}
button.btn-assign:hover { background: #4446d2; }


/* Button color variants */
button.btn-assign.gray { background: #888; }
button.btn-assign.green { background: #28a745; }
button.btn-assign.orange { background: #ff8800; }
button.btn-assign.blue { background: #007bff; }
/* Ensure hover respects variant (simple rule) */
button.btn-assign.green:hover { background: #218838; }
button.btn-assign.orange:hover { background: #e67600; }
button.btn-assign.blue:hover { background: #0069d9; }
.time-info {
  font-size: 13px;
  color: #666;
  padding-top: 4px;
  margin-top: 4px;
  border-top: 1px dashed #ddd;
}
</style>
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <div class="brand">
      <a href="index.html"><img src="../assets/images/logo.svg" alt="Smart Maintenance Logo"></a>
    </div>
    <nav class="nav">
      <a href="index.html">🏠 Home</a>
      <a href="student.php">👩‍🎓 Student</a>
      <a href="technician.php">🛠️ Technician</a>
      <a href="logout.php">🔒 Logout</a>
    </nav>
  </div>
</header>

<div class="container">
  <h2>👨‍💼 Welcome, <?= htmlspecialchars($name) ?></h2>

  <div class="dashboard-buttons">
    <a href="#" onclick="showRequests()">📋 Manage Requests</a>
    <a href="reports.php">⭐ View Reports</a>
  </div>

  <section id="requestsSection">
    <h3>All Maintenance Requests</h3>

    <?php if(count($requests)===0): ?>
      <p>No maintenance requests found.</p>
    <?php else: foreach($requests as $r): ?>
      <div class="request-card">
        <div class="request-title">#<?= $r['request_id'] ?> — <?= htmlspecialchars($r['title']) ?></div>

        <div class="request-row">
          <span><strong>Type:</strong> <?= htmlspecialchars($r['tech_type']) ?></span>
          <span><strong>Location:</strong> <?= htmlspecialchars($r['location']) ?></span>
        </div>


        <div class="request-row">
          <span><strong>Student:</strong> <?= htmlspecialchars($r['student_name']) ?></span>
        </div>

        <div class="request-row controls-row" data-request-id="<?= $r['request_id'] ?>">
          <!-- Technician Type -->
          <label>Technician Type:
            <select name="tech_type" onchange="loadTechnicians(this, <?= $r['request_id'] ?>)">
              <option value="">Select Type</option>
              <option value="Electrical">Electrical</option>
              <option value="Plumbing">Plumbing</option>
              <option value="Carpentry">Carpentry</option>
              <option value="Painting">Painting</option>
              <option value="Other">Other</option>
            </select>
          </label>

          <!-- Technician dropdown (populated via AJAX) -->
          <label>Technician:
            <select name="tech_id" disabled>
              <option value="">Select Technician</option>
            </select>
          </label>

          <!-- Single assign/status button -->
          <?php
            $status = $r['status'] ?? '';
            $btn_text = 'Assign';
            $btn_class = 'gray';
            $btn_disabled = '';
            if (!$r['technician_id'] || $r['technician_id'] == 0 || strtolower($status) === 'not assigned') {
              $btn_text = 'Assign';
              $btn_class = 'gray';
              $btn_disabled = '';
            } elseif (strtolower($status) === 'assigned') {
              $btn_text = 'Assigned ✓';
              $btn_class = 'green';
              $btn_disabled = 'disabled';
            } elseif (strtolower($status) === 'in progress' || strtolower($status) === 'in_progress') {
              $btn_text = 'In Progress ⏳';
              $btn_class = 'orange';
              $btn_disabled = 'disabled';
            } elseif (strtolower($status) === 'completed') {
              $btn_text = 'Completed ✅';
              $btn_class = 'blue';
              $btn_disabled = 'disabled';
            }
          ?>
          <button type="button" class="btn-assign <?= $btn_class ?>" data-request-id="<?= $r['request_id'] ?>" <?= $btn_disabled ?>>
            <?= $btn_text ?>
          </button>
        </div>

      </div>
    <?php endforeach; endif; ?>
  </section>
</div>

<script>
function showRequests() {
  document.getElementById('requestsSection').scrollIntoView({ behavior: 'smooth' });
}


async function loadTechnicians(typeSelect, requestId) {
  const controls = typeSelect.closest('.controls-row');
  const techSelect = controls.querySelector('select[name="tech_id"]');
  const assignBtn = controls.querySelector('.btn-assign');
  const type = typeSelect.value;

  techSelect.innerHTML = '<option>Loading...</option>';
  techSelect.disabled = true;
  assignBtn.disabled = true;

  if (!type) {
    techSelect.innerHTML = '<option value="">Select Technician</option>';
    techSelect.disabled = true;
    return;
  }

  try {
    const res = await fetch(`../backend/fetch_technicians.php?tech_type=${encodeURIComponent(type)}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'No technicians found');
    techSelect.innerHTML = '<option value="">Select Technician</option>';
    data.techs.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.user_id;
      opt.textContent = t.name;
      techSelect.appendChild(opt);
    });
    techSelect.disabled = false;
    techSelect.onchange = () => {
      assignBtn.disabled = !techSelect.value;
    };
  } catch (err) {
    console.error('Fetch error:', err);
    techSelect.innerHTML = '<option value="">No technicians</option>';
    techSelect.disabled = true;
    assignBtn.disabled = true;
    alert('Error fetching technicians: ' + err.message);
  }
}

// Handle clicks on assign buttons
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-assign');
  if (!btn) return;
  if (btn.disabled) return;
  const reqId = btn.getAttribute('data-request-id');
  const controls = document.querySelector('.controls-row[data-request-id="' + reqId + '"]');
  if (!controls) return;
  const techSelect = controls.querySelector('select[name="tech_id"]');
  const typeSelect = controls.querySelector('select[name="tech_type"]');
  const techId = techSelect ? techSelect.value : '';
  const techType = typeSelect ? typeSelect.value : '';
  if (!techId) return alert('Please select a technician before assigning.');

  btn.disabled = true;
  btn.textContent = 'Assigning...';

  try {
    const res = await fetch('../backend/assign_technician.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ request_id: reqId, technician_id: techId, technician_type: techType })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Assign failed');
    // Update UI to Assigned
    btn.classList.remove('gray','orange','blue');
    btn.classList.add('green');
    btn.textContent = 'Assigned ✓';
    btn.disabled = true;
  } catch (err) {
    console.error('Assign error:', err);
    alert('Assign error: ' + (err.message || 'Unknown'));
    btn.disabled = false;
    btn.textContent = 'Assign';
  }
});

// Polling to update statuses every 5 seconds
async function pollStatuses() {
  try {
    const res = await fetch('../backend/fetch_requests.php');
    const data = await res.json();
    if (!data.success) return;
    data.requests.forEach(r => {
      const controls = document.querySelector('.controls-row[data-request-id="' + r.request_id + '"]');
      if (!controls) return;
      const btn = controls.querySelector('.btn-assign');
      if (!btn) return;
      const status = (r.status || '').toLowerCase();
      btn.classList.remove('gray','green','orange','blue');
      if (!r.technician_id || r.technician_id == 0 || status === 'not assigned') {
        btn.classList.add('gray');
        btn.textContent = 'Assign';
        btn.disabled = false;
      } else if (status === 'assigned') {
        btn.classList.add('green');
        btn.textContent = 'Assigned ✓';
        btn.disabled = true;
      } else if (status === 'in progress' || status === 'in_progress') {
        btn.classList.add('orange');
        btn.textContent = 'In Progress ⏳';
        btn.disabled = true;
      } else if (status === 'completed') {
        btn.classList.add('blue');
        btn.textContent = 'Completed ✅';
        btn.disabled = true;
      } else {
        // unknown status - show as gray but disabled
        btn.classList.add('gray');
        btn.textContent = r.status || 'Assign';
        btn.disabled = false;
      }
    });
  } catch (err) {
    console.error('Polling error:', err);
  }
}

// start polling
setInterval(pollStatuses, 5000);
window.addEventListener('load', pollStatuses);
</script>
</body>
</html>
