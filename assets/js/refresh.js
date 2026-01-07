async function postForm(url, form) {
  const res = await fetch(url, { method:'POST', body: form, credentials: 'same-origin' });
  const text = await res.text();
  try { return JSON.parse(text); } catch (e) { return { success:false, error: 'Invalid response: '+text }; }
}

function applyButtonStyle(btn, status) {
  if (!btn) return;
  btn.classList.remove('assign-btn','assigned-btn','inprogress-btn','completed-btn');
  if (status === 'Assigned') { btn.textContent = 'Assigned ✓'; btn.classList.add('assigned-btn'); btn.disabled = true; }
  else if (status === 'In Progress') { btn.textContent = 'In Progress ⏳'; btn.classList.add('inprogress-btn'); btn.disabled = true; }
  else if (status === 'Completed') { btn.textContent = 'Completed ✅'; btn.classList.add('completed-btn'); btn.disabled = true; }
  else { btn.textContent = 'Assign'; btn.classList.add('assign-btn'); btn.disabled = false; }
}

function updateRowUI(r) {
  if (!r) return;
  const row = document.querySelector('#request-' + r.request_id);
  if (!row) return;
  const techCell = row.querySelector('.technician-cell');
  if (techCell) {
    if (r.technician_id) {
      techCell.innerHTML = '<span class="assigned-tech">' + (r.technician_name || '') + '</span><br><small><strong>Type:</strong> ' + (r.tech_type || '') + '</small>';
    } else {
      // leave selects as-is (they exist in DOM); do nothing
    }
  }
  const btn = row.querySelector('.assign-action');
  applyButtonStyle(btn, r.status);
  // disable selects if assigned/in progress/completed
  const typeSelect = row.querySelector('.type-select');
  const techSelect = row.querySelector('.tech-select');
  if (r.status === 'Assigned' || r.status === 'In Progress' || r.status === 'Completed') {
    if (typeSelect) typeSelect.disabled = true;
    if (techSelect) techSelect.disabled = true;
  } else {
    if (typeSelect) typeSelect.disabled = false;
    if (techSelect) techSelect.disabled = false;
  }
}

async function assignTechAjax(requestId) {
  const tSel = document.querySelector('#tech-select-' + requestId);
  const typeSel = document.querySelector('#tech-type-' + requestId);
  const btn = document.querySelector('#assign-btn-' + requestId);
  if (!tSel || !typeSel || !btn) return alert('Assign UI not found');
  const techId = tSel.value;
  const techType = typeSel.value;
  if (!techType) return alert('Please select type');
  if (!techId) return alert('Please select technician');
  btn.disabled = true;
  btn.textContent = 'Assigning...';
  const form = new FormData();
  form.append('request_id', requestId);
  form.append('technician_id', techId);
  form.append('technician_type', techType);
  const res = await postForm('../backend/assign_technician.php', form);
  if (res.success && res.request) {
    updateRowUI(res.request);
  } else {
    alert('Error: ' + (res.error || 'Unknown'));
    btn.disabled = false;
    btn.textContent = 'Assign';
  }
}

async function pollRequests() {
  try {
    const res = await fetch('../backend/fetch_requests.php', { credentials: 'same-origin' });
    const text = await res.text();
    const data = JSON.parse(text);
    if (!data.success) return;
    data.requests.forEach(r => updateRowUI(r));
  } catch (e) {
    console.error('Polling error', e);
  }
}

setInterval(pollRequests, 5000);
document.addEventListener('DOMContentLoaded', pollRequests);
