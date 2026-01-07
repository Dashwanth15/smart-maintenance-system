
/* app.js: AJAX actions and polling every 5 seconds - final behavior */
/* Status button mapping:
   Not Assigned -> Assign (enabled, gray)
   Assigned -> Assigned ✓ (enabled, green)
   In Progress -> In Progress ⏳ (disabled, orange)
   Completed -> Completed ✅ (disabled, blue)
*/

async function postForm(url, form) {
  const res = await fetch(url, { method:'POST', body: form, credentials: 'same-origin' });
  const text = await res.text();
  try { return JSON.parse(text); } catch (e) { return { success:false, error:'Invalid server response: '+text }; }
}

async function assignTechAjax(requestId) {
  const typeSelect = document.querySelector(`#tech-type-${requestId}`);
  const techSelect = document.querySelector(`#tech-select-${requestId}`);
  const assignBtn = document.querySelector(`#assign-btn-${requestId}`);
  if (!typeSelect || !techSelect || !assignBtn) return alert('Assign elements not found');
  const techType = typeSelect.value;
  const techId = techSelect.value;
  if (!techType) return alert('Please select technician type');
  if (!techId) return alert('Please select technician');

  const form = new FormData();
  form.append('request_id', requestId);
  form.append('technician_id', techId);
  form.append('technician_type', techType);

  assignBtn.disabled = true;
  assignBtn.textContent = 'Assigning...';

  const res = await postForm('../backend/assign_technician.php', form);
  if (res.success) {
    // update UI inline
    updateRowUI(res.request);
    // trigger poll for other pages
    if (typeof pollRequests === 'function') pollRequests();
  } else {
    alert('Error: ' + (res.error || 'Unknown'));
    assignBtn.disabled = false;
    assignBtn.textContent = 'Assign';
  }
}

async function startWork(requestId) {
  if (!confirm('Start work on request #' + requestId + '?')) return;
  const form = new FormData();
  form.append('request_id', requestId);
  const res = await postForm('..../backend/start_work.php', form);
  if (res.success) {
    updateRowUI(res.request);
    if (typeof pollRequests === 'function') pollRequests();
  } else {
    alert('Error: ' + (res.error || 'Unknown'));
  }
}

async function markCompleted(requestId) {
  if (!confirm('Mark request #' + requestId + ' as completed?')) return;
  const form = new FormData();
  form.append('request_id', requestId);
  const res = await postForm('..../backend/mark_completed.php', form);
  if (res.success) {
    updateRowUI(res.request);
    if (typeof pollRequests === 'function') pollRequests();
  } else {
    alert('Error: ' + (res.error || 'Unknown'));
  }
}

function setButtonState(btn, status) {
  if (!btn) return;
  btn.classList.remove('assign-btn','assigned-btn','inprogress-btn','completed-btn');
  btn.disabled = false;
  if (status === 'Assigned') {
    btn.textContent = 'Assigned ✓';
    btn.classList.add('assigned-btn');
    btn.disabled = false; // admin button still enabled? per final, admin assign stays disabled after assigning; but technician actions will disable later
  } else if (status === 'In Progress') {
    btn.textContent = 'In Progress ⏳';
    btn.classList.add('inprogress-btn');
    btn.disabled = true;
  } else if (status === 'Completed') {
    btn.textContent = 'Completed ✅';
    btn.classList.add('completed-btn');
    btn.disabled = true;
  } else {
    btn.textContent = 'Assign';
    btn.classList.add('assign-btn');
    btn.disabled = false;
  }
}

// Update the UI for a single request row based on the request object
function updateRowUI(r) {
  if (!r) return;
  const rid = r.request_id;
  const row = document.querySelector('#request-' + rid);
  if (!row) return;
  // status text element
  const statusEl = row.querySelector('.status-text');
  if (statusEl) statusEl.textContent = r.status;
  // tech cell
  const techCell = row.querySelector('.technician-cell');
  if (techCell) {
    if (r.technician_id) {
      techCell.innerHTML = '<strong>Technician:</strong> ' + (r.technician_name || '') + '<br><small><strong>Type:</strong> ' + (r.tech_type || '') + '</small>';
    } else {
      // restore selects if unassigned (unlikely after assign)
      techCell.innerHTML = techCell.getAttribute('data-original') || techCell.innerHTML;
    }
  }
  // start/end times
  const startEl = row.querySelector('.start-time');
  if (startEl) startEl.textContent = r.start_time || '';
  const endEl = row.querySelector('.end-time');
  if (endEl) endEl.textContent = r.end_time || '';
  // assign button
  const assignBtn = row.querySelector('.assign-action');
  const typeSelect = row.querySelector('.type-select');
  const techSelect = row.querySelector('.tech-select');
  // Set button state per mapping. After admin assigns, admin button should be disabled (we'll disable here if assigned).
  if (r.status === 'Assigned') {
    setButtonState(assignBtn, 'Assigned');
    if (assignBtn) assignBtn.disabled = true;
    if (typeSelect) typeSelect.disabled = true;
    if (techSelect) techSelect.disabled = true;
  } else if (r.status === 'In Progress') {
    setButtonState(assignBtn, 'In Progress');
    if (assignBtn) assignBtn.disabled = true;
    if (typeSelect) typeSelect.disabled = true;
    if (techSelect) techSelect.disabled = true;
  } else if (r.status === 'Completed') {
    setButtonState(assignBtn, 'Completed');
    if (assignBtn) assignBtn.disabled = true;
    if (typeSelect) typeSelect.disabled = true;
    if (techSelect) techSelect.disabled = true;
  } else {
    setButtonState(assignBtn, 'Not Assigned');
    if (assignBtn) assignBtn.disabled = false;
    if (typeSelect) typeSelect.disabled = false;
    if (techSelect) techSelect.disabled = false;
  }
}

// Polling
async function pollRequests() {
  try {
    const res = await fetch('../backend/fetch_requests.php', { credentials: 'same-origin' });
    const text = await res.text();
    const data = JSON.parse(text);
    if (!data.success) return;
    const requests = data.requests || [];
    requests.forEach(r => {
      updateRowUI(r);
    });
  } catch (e) {
    console.error('Polling error', e);
  }
}

setInterval(pollRequests, 5000);
document.addEventListener('DOMContentLoaded', pollRequests);


/* --- Technician action buttons (start / complete) ---
   Uses event delegation on elements with class 'status-btn' and data-action attribute.
*/
document.addEventListener('click', async function(ev){
  const btn = ev.target.closest && ev.target.closest('.status-btn');
  if (!btn) return;
  ev.preventDefault();
  const action = btn.dataset.action; // 'start' or 'complete'
  const requestId = btn.dataset.id;
  if (!action || !requestId) return;
  btn.disabled = true;
  const form = new FormData();
  form.append('request_id', requestId);
  try {
    if (action === 'start') {
      const res = await postForm('../backend/start_work.php', form);
      if (!res.success) { alert(res.error || 'Failed to start'); btn.disabled=false; return; }
    } else if (action === 'complete') {
      const res = await postForm('../backend/mark_completed.php', form);
      if (!res.success) { alert(res.error || 'Failed to complete'); btn.disabled=false; return; }
    } else {
      btn.disabled = false;
      return;
    }
    // Refresh requests immediately after action
    if (typeof pollRequests === 'function') pollRequests();
  } catch (err) {
    console.error(err);
    alert('Request failed: ' + err);
  } finally {
    btn.disabled = false;
  }
}, false);

