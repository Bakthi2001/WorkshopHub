/* ============================================================
   SLPI Admin Panel — admin.js
   Shared data layer + utilities for all three admin panels.
   Uses localStorage as the persistence layer so public
   index.html can read the same data.
   ============================================================ */

/* ── CREDENTIALS ── */
const ADMINS = {
  admin1: { user: 'admin1', pass: 'Admin1@2025', role: 'admin1', label: 'Admin 1', color: 'em'   },
  admin2: { user: 'admin2', pass: 'Admin2@2025', role: 'admin2', label: 'Admin 2', color: 'blue' },
  super:  { user: 'super',  pass: 'Super@2025',  role: 'super',  label: 'Super Admin', color: 'gold' },
};

/* ── STORAGE KEYS ── */
const KEY_WS     = 'slpi_workshops';
const KEY_ENROLL = 'slpi_enrollments';
const KEY_EMAIL_LOG = 'slpi_email_log';

/* ── SEED DATA (pre-loaded workshops) ── */
const SEED_WORKSHOPS = [
  { id:'ws-001', owner:'admin1', title:'Digital Journalism Fundamentals', category:'Digital', date:'2025-06-15', time:'9:00 AM – 5:00 PM', location:'SLPI Auditorium, Colombo', trainer:'Sarah Johnson', description:'Master the essentials of digital news reporting, social media storytelling, and online publishing platforms.', image:'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=600&h=300&fit=crop', maxParticipants:30, isActive:true, createdAt:'2025-05-01' },
  { id:'ws-002', owner:'admin1', title:'Media Ethics & Integrity', category:'Ethics', date:'2025-07-03', time:'10:00 AM – 4:00 PM', location:'Online (Zoom)', trainer:'Michael Chen', description:'Explore the ethical frameworks that guide responsible journalism, press freedom, and accountability reporting.', image:'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=600&h=300&fit=crop', maxParticipants:25, isActive:true, createdAt:'2025-05-02' },
  { id:'ws-003', owner:'admin2', title:'Video Storytelling Workshop', category:'Video', date:'2025-07-20', time:'9:00 AM – 6:00 PM', location:'Media Lab, SLPI', trainer:'Emma Wilson', description:'Learn cinematic techniques for broadcast journalism, documentary production, and mobile video reporting.', image:'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=300&fit=crop', maxParticipants:20, isActive:true, createdAt:'2025-05-03' },
  { id:'ws-004', owner:'admin2', title:'Investigative Reporting', category:'Writing', date:'2025-08-05', time:'9:00 AM – 5:00 PM', location:'SLPI Conference Room', trainer:'David Park', description:'Deep-dive into source protection, FOIA requests, data analysis, and long-form investigative storytelling.', image:'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&h=300&fit=crop', maxParticipants:20, isActive:true, createdAt:'2025-05-04' },
  { id:'ws-005', owner:'admin1', title:'Data Journalism & Visualization', category:'Digital', date:'2025-08-18', time:'10:00 AM – 5:00 PM', location:'Online (Zoom)', trainer:'Sarah Johnson', description:'Transform raw data into compelling stories using modern tools, charts, and interactive visualizations.', image:'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600&h=300&fit=crop', maxParticipants:30, isActive:true, createdAt:'2025-05-05' },
  { id:'ws-006', owner:'admin2', title:'News Photography', category:'Writing', date:'2025-09-02', time:'8:00 AM – 4:00 PM', location:'SLPI Studio, Colombo', trainer:'Emma Wilson', description:'Develop your visual narrative skills — from breaking news shots to portrait photography.', image:'https://images.unsplash.com/photo-1456324504439-367cee3b3c32?w=600&h=300&fit=crop', maxParticipants:15, isActive:true, createdAt:'2025-05-06' },
];

const SEED_ENROLLMENTS = [
  { id:'en-001', workshopId:'ws-001', name:'Amal Perera',   email:'amal@email.com',   phone:'0771234567', nic:'199012345678', workplace:'Daily Mirror',   enrolledAt:'2025-05-10', status:'enrolled', selected:false },
  { id:'en-002', workshopId:'ws-001', name:'Nadia Fernando', email:'nadia@email.com', phone:'0779876543', nic:'199512345678', workplace:'Independent',    enrolledAt:'2025-05-11', status:'enrolled', selected:false },
  { id:'en-003', workshopId:'ws-001', name:'Kasun Silva',   email:'kasun@email.com',  phone:'0772345678', nic:'200012345678', workplace:'Student',        enrolledAt:'2025-05-12', status:'enrolled', selected:false },
  { id:'en-004', workshopId:'ws-002', name:'Priya Raj',     email:'priya@email.com',  phone:'0773456789', nic:'199712345678', workplace:'Colombo Gazette', enrolledAt:'2025-05-13', status:'enrolled', selected:false },
  { id:'en-005', workshopId:'ws-002', name:'Sampath Wijesinghe', email:'sampath@email.com', phone:'0774567890', nic:'200112345678', workplace:'Freelance', enrolledAt:'2025-05-14', status:'enrolled', selected:false },
  { id:'en-006', workshopId:'ws-003', name:'Tharushi Bandara', email:'tharushi@email.com', phone:'0775678901', nic:'199812345678', workplace:'TV1',       enrolledAt:'2025-05-15', status:'enrolled', selected:false },
  { id:'en-007', workshopId:'ws-003', name:'Dilan Jayawardena', email:'dilan@email.com', phone:'0776789012', nic:'200212345678', workplace:'Student',    enrolledAt:'2025-05-16', status:'enrolled', selected:false },
  { id:'en-008', workshopId:'ws-004', name:'Malki Senanayake', email:'malki@email.com', phone:'0777890123', nic:'199912345678', workplace:'Sunday Times', enrolledAt:'2025-05-17', status:'enrolled', selected:false },
  { id:'en-009', workshopId:'ws-005', name:'Ranil Mendis',   email:'ranil@email.com',  phone:'0778901234', nic:'200312345678', workplace:'Ada Derana',   enrolledAt:'2025-05-18', status:'enrolled', selected:false },
  { id:'en-010', workshopId:'ws-006', name:'Hiruni Pathirana', email:'hiruni@email.com', phone:'0779012345', nic:'200412345678', workplace:'Student',   enrolledAt:'2025-05-19', status:'enrolled', selected:false },
];

/* ── INIT ── */
function initStorage() {
  if (!localStorage.getItem(KEY_WS))     localStorage.setItem(KEY_WS,     JSON.stringify(SEED_WORKSHOPS));
  if (!localStorage.getItem(KEY_ENROLL)) localStorage.setItem(KEY_ENROLL, JSON.stringify(SEED_ENROLLMENTS));
  if (!localStorage.getItem(KEY_EMAIL_LOG)) localStorage.setItem(KEY_EMAIL_LOG, JSON.stringify([]));
}

/* ── DATA HELPERS ── */
function getWorkshops()    { return JSON.parse(localStorage.getItem(KEY_WS)     || '[]'); }
function getEnrollments()  { return JSON.parse(localStorage.getItem(KEY_ENROLL) || '[]'); }
function getEmailLog()     { return JSON.parse(localStorage.getItem(KEY_EMAIL_LOG) || '[]'); }

function saveWorkshops(data)   { localStorage.setItem(KEY_WS,     JSON.stringify(data)); }
function saveEnrollments(data) { localStorage.setItem(KEY_ENROLL, JSON.stringify(data)); }
function saveEmailLog(data)    { localStorage.setItem(KEY_EMAIL_LOG, JSON.stringify(data)); }

function genId(prefix) { return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2,7)}`; }

/* ── EMAIL SIMULATION ── */
function simulateRejectionEmail(enrollment, workshop) {
  const log = getEmailLog();
  const entry = {
    id:       genId('mail'),
    to:       enrollment.email,
    name:     enrollment.name,
    workshop: workshop.title,
    sentAt:   new Date().toISOString(),
    subject:  `Your enrollment in "${workshop.title}" has been cancelled`,
    body:     `Dear ${enrollment.name},\n\nWe regret to inform you that your enrollment in the workshop "${workshop.title}" scheduled for ${workshop.date} has been removed by the organiser.\n\nIf you have questions, please contact us at info@slpi.lk.\n\nBest regards,\nSLPI Workshop Hub`,
  };
  log.unshift(entry);
  saveEmailLog(log);
  return entry;
}

/* ── WORKSHOP CRUD ── */
function addWorkshop(data, owner) {
  const workshops = getWorkshops();
  const ws = { ...data, id: genId('ws'), owner, isActive: true, createdAt: new Date().toISOString().split('T')[0] };
  workshops.push(ws);
  saveWorkshops(workshops);
  return ws;
}

function updateWorkshop(id, data) {
  const workshops = getWorkshops();
  const idx = workshops.findIndex(w => w.id === id);
  if (idx === -1) return false;
  workshops[idx] = { ...workshops[idx], ...data };
  saveWorkshops(workshops);
  return true;
}

function deleteWorkshop(id) {
  let workshops = getWorkshops();
  workshops = workshops.filter(w => w.id !== id);
  saveWorkshops(workshops);
  // Also remove enrollments
  let enrollments = getEnrollments();
  enrollments = enrollments.filter(e => e.workshopId !== id);
  saveEnrollments(enrollments);
}

/* ── ENROLLMENT CRUD ── */
function getWorkshopEnrollments(workshopId) {
  return getEnrollments().filter(e => e.workshopId === workshopId);
}

function removeEnrollment(enrollmentId) {
  const enrollments = getEnrollments();
  const entry = enrollments.find(e => e.id === enrollmentId);
  if (!entry) return null;
  const workshops = getWorkshops();
  const ws = workshops.find(w => w.id === entry.workshopId);
  const updated = enrollments.filter(e => e.id !== enrollmentId);
  saveEnrollments(updated);
  if (ws) simulateRejectionEmail(entry, ws);
  return entry;
}

function toggleSelected(enrollmentId) {
  const enrollments = getEnrollments();
  const idx = enrollments.findIndex(e => e.id === enrollmentId);
  if (idx === -1) return;
  enrollments[idx].selected = !enrollments[idx].selected;
  saveEnrollments(enrollments);
}

/* ── EXPORT CSV ── */
function exportToCSV(rows, filename) {
  if (!rows.length) { showToast('No data to export', 'error'); return; }
  const headers = ['Name','Email','Phone','NIC','Workplace','Workshop','Enrolled Date','Status','Selected'];
  const workshops = getWorkshops();
  const lines = rows.map(e => {
    const ws = workshops.find(w => w.id === e.workshopId);
    return [
      `"${e.name}"`, `"${e.email}"`, `"${e.phone}"`, `"${e.nic}"`,
      `"${e.workplace}"`, `"${ws ? ws.title : e.workshopId}"`,
      `"${e.enrolledAt}"`, `"${e.status}"`, `"${e.selected ? 'Yes':'No'}"`,
    ].join(',');
  });
  const csv = [headers.join(','), ...lines].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
  showToast('CSV exported successfully!', 'success');
}

/* ── GOOGLE SHEETS EXPORT (opens prefilled URL) ── */
function exportToGoogleSheets(rows, sheetName) {
  if (!rows.length) { showToast('No data to export', 'error'); return; }
  // We create a CSV and open a Google Sheets import URL
  exportToCSV(rows, `${sheetName}_${Date.now()}.csv`);
  showToast('CSV downloaded — import it into Google Sheets via File → Import', 'info');
}

/* ── TOAST ── */
let _toastContainer = null;
function showToast(msg, type = 'success') {
  if (!_toastContainer) {
    _toastContainer = document.createElement('div');
    _toastContainer.className = 'toast-container';
    document.body.appendChild(_toastContainer);
  }
  const icons = {
    success: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    error:   '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
    info:    '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
  };
  const t = document.createElement('div');
  t.className = `toast toast--${type}`;
  t.innerHTML = (icons[type] || '') + `<span>${msg}</span>`;
  _toastContainer.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3500);
}

/* ── CONFIRM DIALOG ── */
function showConfirm(message, onConfirm) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay open';
  overlay.innerHTML = `
    <div class="confirm-box">
      <h3>Confirm Action</h3>
      <p>${message}</p>
      <div class="confirm-actions">
        <button class="btn-primary" id="confirmYes" style="background:var(--red);">Yes, proceed</button>
        <button class="btn-ghost"   id="confirmNo">Cancel</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  document.getElementById('confirmYes').onclick = () => { overlay.remove(); onConfirm(); };
  document.getElementById('confirmNo').onclick  = () => { overlay.remove(); };
  overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
}

/* ── SESSION ── */
function getSession() {
  try { return JSON.parse(sessionStorage.getItem('slpi_admin_session') || 'null'); } catch { return null; }
}
function setSession(admin) { sessionStorage.setItem('slpi_admin_session', JSON.stringify(admin)); }
function clearSession()    { sessionStorage.removeItem('slpi_admin_session'); }

/* ── DATE HELPER ── */
function fmtDate(d) {
  if (!d) return '—';
  const dt = new Date(d);
  if (isNaN(dt)) return d;
  return dt.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
}

/* ── SIDEBAR TOGGLE (mobile) ── */
function initMobileSidebar() {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (!toggle || !sidebar) return;
  toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && e.target !== toggle) sidebar.classList.remove('open');
  });
}

/* ── NAV ── */
function initNav(activateFn) {
  document.querySelectorAll('.sidebar-link[data-section]').forEach(link => {
    link.addEventListener('click', () => {
      const sec = link.dataset.section;
      document.querySelectorAll('.sidebar-link[data-section]').forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
      const target = document.getElementById('sec-' + sec);
      if (target) target.classList.add('active');
      document.getElementById('topbarTitle').textContent = link.querySelector('.link-label')?.textContent || '';
      if (activateFn) activateFn(sec);
      // Mobile — close sidebar
      document.querySelector('.admin-sidebar')?.classList.remove('open');
    });
  });
}

/* ── LOGOUT ── */
function initLogout() {
  document.getElementById('logoutBtn')?.addEventListener('click', () => {
    clearSession();
    const role = getSession()?.role || '';
    const map = { admin1: 'admin1-login.html', admin2: 'admin2-login.html', super: 'super-login.html' };
    window.location.href = map[role] || 'admin1-login.html';
  });
}

/* ── WORKSHOP FORM ── */
function buildWorkshopForm(ws = null) {
  return `
    <div class="form-grid">
      <div class="form-group full">
        <label>Workshop Title *</label>
        <input id="wf-title" type="text" placeholder="e.g. Digital Journalism Fundamentals" value="${ws?.title||''}" required />
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select id="wf-category">
          ${['Digital','Ethics','Video','Writing','Photography','Other'].map(c=>`<option value="${c}" ${ws?.category===c?'selected':''}>${c}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Trainer / Instructor *</label>
        <input id="wf-trainer" type="text" value="${ws?.trainer||''}" placeholder="e.g. Sarah Johnson" required />
      </div>
      <div class="form-group">
        <label>Date *</label>
        <input id="wf-date" type="date" value="${ws?.date||''}" required />
      </div>
      <div class="form-group">
        <label>Time</label>
        <input id="wf-time" type="text" value="${ws?.time||''}" placeholder="e.g. 9:00 AM – 5:00 PM" />
      </div>
      <div class="form-group">
        <label>Location</label>
        <input id="wf-location" type="text" value="${ws?.location||''}" placeholder="e.g. SLPI Auditorium, Colombo" />
      </div>
      <div class="form-group">
        <label>Max Participants</label>
        <input id="wf-max" type="number" min="1" value="${ws?.maxParticipants||30}" />
      </div>
      <div class="form-group full">
        <label>Description</label>
        <textarea id="wf-desc" rows="4" placeholder="Describe the workshop…">${ws?.description||''}</textarea>
      </div>
      <div class="form-group full">
        <label>Image URL (optional)</label>
        <input id="wf-image" type="url" value="${ws?.image||''}" placeholder="https://images.unsplash.com/…" />
      </div>
    </div>`;
}

function readWorkshopForm() {
  return {
    title:          document.getElementById('wf-title')?.value.trim(),
    category:       document.getElementById('wf-category')?.value,
    trainer:        document.getElementById('wf-trainer')?.value.trim(),
    date:           document.getElementById('wf-date')?.value,
    time:           document.getElementById('wf-time')?.value.trim(),
    location:       document.getElementById('wf-location')?.value.trim(),
    maxParticipants:parseInt(document.getElementById('wf-max')?.value || '30'),
    description:    document.getElementById('wf-desc')?.value.trim(),
    image:          document.getElementById('wf-image')?.value.trim(),
  };
}

/* ── RENDER WORKSHOP ROW ── */
function wsStatusPill(ws) {
  return ws.isActive
    ? `<span class="pill pill-em">Active</span>`
    : `<span class="pill pill-gray">Inactive</span>`;
}

function enrollCount(wsId) {
  return getEnrollments().filter(e => e.workshopId === wsId).length;
}

/* Init on load */
initStorage();
