<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: login.html'); exit; }
include __DIR__ . '/../backend/db.php';
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Reviews - Admin</title><link rel="stylesheet" href="../assets/css/style.css"></head>
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

<header class="bar"><h3>All Reviews</h3><a class="btn" href="admin.php">Back to Admin</a></header>
<main class="container">
  <section>
    <h4>Student Reviews</h4>
    <div id="reviewsList"></div>
  </section>
</main>
<script>
async function loadReviews(){ const res=await fetch('../backend/fetch_reviews.php'); const j=await res.json(); const wrap=document.getElementById('reviewsList'); wrap.innerHTML=''; if(j.success){ if(j.reviews.length===0){ wrap.innerHTML='<p>No reviews yet.</p>'; return; } j.reviews.forEach(rv=>{ const card=document.createElement('div'); card.className='card'; card.innerHTML = `<strong>#${rv.review_id} — Technician: ${rv.technician_name}</strong><p>Student: ${rv.student_name} | Rating: ${rv.rating}/5</p><p>${rv.feedback}</p><p class="muted small">On: ${rv.created_at}</p>`; wrap.appendChild(card); }); } else wrap.innerHTML='<p>Error loading reviews</p>'; } loadReviews();</script></html>