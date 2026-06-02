/* ===========================
   SLPI Workshop Hub — main.js
   =========================== */

// Set copyright year
const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = new Date().getFullYear();

// ===== MOBILE NAV =====
const hamburger = document.getElementById('hamburger');
const mobileNav = document.getElementById('mobile-nav');

if (hamburger && mobileNav) {
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileNav.classList.toggle('open');
  });
}

// ===== SEARCH & FILTER (homepage) =====
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const workshopsGrid = document.getElementById('workshopsGrid');
const noResults = document.getElementById('noResults');

function filterWorkshops() {
  if (!workshopsGrid) return;
  const term = searchInput?.value.toLowerCase().trim() || '';
  const cat = categoryFilter?.value || 'All';
  const cards = workshopsGrid.querySelectorAll('.workshop-card');
  let visible = 0;

  cards.forEach(card => {
    const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
    const desc = card.querySelector('.card-desc')?.textContent.toLowerCase() || '';
    const cardCat = card.dataset.category || '';
    const matchSearch = !term || title.includes(term) || desc.includes(term);
    const matchCat = cat === 'All' || cardCat === cat;

    if (matchSearch && matchCat) {
      card.style.display = '';
      visible++;
    } else {
      card.style.display = 'none';
    }
  });

  if (noResults) {
    noResults.style.display = visible === 0 ? 'block' : 'none';
  }
}

if (searchInput) searchInput.addEventListener('input', filterWorkshops);
if (categoryFilter) categoryFilter.addEventListener('change', filterWorkshops);

// ===== ENROLL MODAL =====
function openModal(workshopId) {
  const overlay = document.getElementById('enrollModal');
  if (overlay) overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  const overlay = document.getElementById('enrollModal');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

// ===== DETAILS MODAL =====
const workshopDetails = {
  'digital-journalism': {
    title: 'Digital Journalism Fundamentals',
    trainer: 'Sarah Johnson',
    desc: 'Master the essentials of digital news reporting, social media storytelling, and online publishing platforms. This full-day workshop covers SEO for journalists, multimedia publishing workflows, and building your digital presence.',
    date: 'June 15, 2025',
    time: '9:00 AM – 5:00 PM',
    location: 'SLPI Auditorium, Colombo',
    category: 'Digital',
  },
  'media-ethics': {
    title: 'Media Ethics & Integrity',
    trainer: 'Michael Chen',
    desc: 'Explore the ethical frameworks that guide responsible journalism, press freedom, and accountability reporting. Learn how to navigate source confidentiality, editorial independence, and conflicts of interest.',
    date: 'July 3, 2025',
    time: '10:00 AM – 4:00 PM',
    location: 'Online (Zoom)',
    category: 'Ethics',
  },
  'video-storytelling': {
    title: 'Video Storytelling',
    trainer: 'Emma Wilson',
    desc: 'Learn cinematic techniques for broadcast journalism, documentary production, and mobile video reporting. Covers shooting, scripting, editing, and distributing video content for modern news organizations.',
    date: 'July 20, 2025',
    time: '9:00 AM – 6:00 PM',
    location: 'Media Lab, SLPI',
    category: 'Video',
  },
  'investigative-reporting': {
    title: 'Investigative Reporting',
    trainer: 'David Park',
    desc: 'Deep-dive into source protection, FOIA requests, data analysis, and long-form investigative storytelling. Practical exercises using real case studies from award-winning investigations.',
    date: 'August 5, 2025',
    time: '9:00 AM – 5:00 PM',
    location: 'SLPI Conference Room',
    category: 'Writing',
  },
  'data-journalism': {
    title: 'Data Journalism & Visualization',
    trainer: 'Sarah Johnson',
    desc: 'Transform raw data into compelling stories using modern tools, charts, and interactive visualizations. Hands-on sessions with spreadsheets, Python basics, and popular visualization libraries.',
    date: 'August 18, 2025',
    time: '10:00 AM – 5:00 PM',
    location: 'Online (Zoom)',
    category: 'Digital',
  },
  'news-photography': {
    title: 'News Photography',
    trainer: 'Emma Wilson',
    desc: 'Develop your visual narrative skills — from breaking news shots to portrait photography for print and digital media. Learn composition, lighting, photo editing, and ethical considerations for news photography.',
    date: 'September 2, 2025',
    time: '8:00 AM – 4:00 PM',
    location: 'SLPI Studio, Colombo',
    category: 'Writing',
  },
};

function openDetails(workshopId) {
  const modal = document.getElementById('detailsModal');
  const content = document.getElementById('detailsContent');
  const data = workshopDetails[workshopId];
  if (!modal || !content || !data) return;

  content.innerHTML = `
    <div class="details-header">
      <button class="modal-close" onclick="closeDetails()">✕</button>
      <h2>${data.title}</h2>
      <p class="details-trainer">Led by ${data.trainer}</p>
    </div>
    <div class="details-body">
      <p class="details-label">Briefing</p>
      <p class="details-desc">${data.desc}</p>
      <div class="details-attrs">
        <div class="details-attr">
          <p>Schedule</p>
          <p>${data.date} @ ${data.time}</p>
        </div>
        <div class="details-attr">
          <p>Venue</p>
          <p>${data.location}</p>
        </div>
      </div>
    </div>
    <div class="details-footer">
      <a href="login.html" class="btn-primary">Enroll Now</a>
      <button class="btn-ghost" onclick="closeDetails()">Close</button>
    </div>
  `;

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDetails() {
  const modal = document.getElementById('detailsModal');
  if (modal) modal.classList.remove('open');
  document.body.style.overflow = '';
}

// Close modals on Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeModal();
    closeDetails();
    document.body.style.overflow = '';
  }
});

// ===== FEEDBACK FORM =====
function submitFeedback(e) {
  e.preventDefault();
  const name = document.getElementById('fbName')?.value.trim();
  const role = document.getElementById('fbRole')?.value.trim();
  const message = document.getElementById('fbMessage')?.value.trim();
  const btn = document.getElementById('feedbackSubmitBtn');
  const success = document.getElementById('feedbackSuccess');
  const form = document.getElementById('feedbackForm');

  if (!name || !role || !message) return;

  if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }

  setTimeout(() => {
    if (form) form.reset();
    if (btn) { btn.disabled = false; btn.textContent = 'Submit Feedback'; }
    if (success) {
      success.style.display = 'flex';
      setTimeout(() => { success.style.display = 'none'; }, 5000);
    }

    // Append new card to testimonials grid
    const grid = document.querySelector('.testimonials-grid');
    if (grid) {
      const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=10b981&color=fff&size=96`;
      const card = document.createElement('div');
      card.className = 'testimonial-card';
      card.style.animation = 'fadeInUp 0.5s ease both';
      card.innerHTML = `
        <div class="testimonial-header">
          <img src="${avatarUrl}" alt="${name}" class="testimonial-avatar" />
          <div>
            <h3 class="testimonial-name">${name}</h3>
            <p class="testimonial-role">${role}</p>
          </div>
        </div>
        <p class="testimonial-text">${message}</p>
      `;
      grid.insertBefore(card, grid.firstChild);
    }
  }, 600);
}

// ===== SCROLL ANIMATIONS =====
const scrollObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });

document.querySelectorAll('.scroll-animate').forEach(el => scrollObserver.observe(el));

// ===== NAV PROFILE CHIP =====
// Shows avatar + name + dropdown in header-actions when user is logged in.
// Replaces the "Log in" / "Sign up" buttons automatically.

(function initNavProfile() {
  const email    = localStorage.getItem('userEmail');
  const name     = localStorage.getItem('userName') || email;
  if (!email) return;                          // not logged in — keep default buttons

  // Build initials (up to 2 chars)
  const initials = name
    .split(/\s+/)
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);

  // ── Inject CSS once ──────────────────────────────────────────
  if (!document.getElementById('nav-profile-style')) {
    const style = document.createElement('style');
    style.id = 'nav-profile-style';
    style.textContent = `
      /* hide the default log-in / sign-up buttons when logged in */
      .nav-logged-in .btn-ghost,
      .nav-logged-in .btn-primary { display: none !important; }

      /* ── profile chip ── */
      .nav-profile {
        position: relative;
        display: flex;
        align-items: center;
        gap: .55rem;
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 2rem;
        padding: .3rem .75rem .3rem .35rem;
        cursor: pointer;
        transition: background .2s, border-color .2s;
        user-select: none;
      }
      .nav-profile:hover {
        background: rgba(255,255,255,.12);
        border-color: rgba(16,185,129,.35);
      }
      .nav-profile-avatar {
        width: 1.85rem; height: 1.85rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #06b6d4);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif;
        font-size: .6rem; font-weight: 800;
        color: #fff; flex-shrink: 0;
      }
      .nav-profile-name {
        font-family: 'Syne', sans-serif;
        font-size: .8rem; font-weight: 700;
        color: #e2e8f0;
        max-width: 90px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .nav-profile-caret {
        font-size: .6rem; color: #64748b;
        transition: transform .2s;
      }
      .nav-profile.open .nav-profile-caret { transform: rotate(180deg); }

      /* ── dropdown ── */
      .nav-profile-dropdown {
        position: absolute;
        top: calc(100% + .55rem);
        right: 0;
        background: #0d1627;
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 1rem;
        padding: .5rem;
        min-width: 180px;
        box-shadow: 0 16px 40px rgba(2,6,23,.7);
        display: none;
        z-index: 300;
        animation: scaleIn .2s cubic-bezier(.34,1.56,.64,1);
      }
      .nav-profile.open .nav-profile-dropdown { display: block; }

      .nav-profile-header {
        padding: .6rem .75rem;
        border-bottom: 1px solid rgba(255,255,255,.07);
        margin-bottom: .3rem;
      }
      .nav-profile-header p:first-child {
        font-size: .8rem; font-weight: 700;
        color: #e2e8f0; font-family: 'Syne', sans-serif;
      }
      .nav-profile-header p:last-child {
        font-size: .7rem; color: #64748b;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
      }

      .nav-dd-link {
        display: flex; align-items: center; gap: .55rem;
        padding: .55rem .75rem;
        border-radius: .6rem;
        font-size: .82rem; color: #94a3b8;
        text-decoration: none;
        transition: background .15s, color .15s;
        cursor: pointer;
        border: none; background: none; width: 100%; text-align: left;
        font-family: 'DM Sans', sans-serif;
      }
      .nav-dd-link:hover { background: rgba(255,255,255,.07); color: #e2e8f0; }
      .nav-dd-link svg  { width: .9rem; height: .9rem; flex-shrink: 0; opacity: .7; }
      .nav-dd-link--danger { color: #fca5a5; }
      .nav-dd-link--danger:hover { background: rgba(239,68,68,.1); color: #fca5a5; }
      .nav-dd-divider { height: 1px; background: rgba(255,255,255,.07); margin: .3rem 0; }

      /* mobile: show profile chip instead of login buttons */
      .mobile-profile-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem 1rem;
        border-radius: .75rem;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.08);
        margin-top: .5rem;
      }
      .mobile-profile-avatar {
        width: 2.2rem; height: 2.2rem; border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #06b6d4);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: .65rem; font-weight: 800;
        color: #fff; flex-shrink: 0;
      }
      .mobile-profile-info p:first-child {
        font-size: .85rem; font-weight: 700;
        color: #e2e8f0; font-family: 'Syne', sans-serif;
      }
      .mobile-profile-info p:last-child { font-size: .72rem; color: #64748b; }
    `;
    document.head.appendChild(style);
  }

  // ── Build chip HTML ────────────────────────────────────────
  const chip = document.createElement('div');
  chip.className = 'nav-profile';
  chip.setAttribute('role', 'button');
  chip.setAttribute('aria-haspopup', 'true');
  chip.setAttribute('aria-expanded', 'false');
  chip.innerHTML = `
    <div class="nav-profile-avatar">${initials}</div>
    <span class="nav-profile-name">${name.split(' ')[0]}</span>
    <span class="nav-profile-caret">▼</span>
    <div class="nav-profile-dropdown" role="menu">
      <div class="nav-profile-header">
        <p>${name}</p>
        <p>${email}</p>
      </div>
      <a href="profile.html" class="nav-dd-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        My Profile
      </a>
      <a href="index.html#workshops" class="nav-dd-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        Browse Workshops
      </a>
      <div class="nav-dd-divider"></div>
      <button class="nav-dd-link nav-dd-link--danger" id="navSignOutBtn">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </button>
    </div>
  `;

  // ── Toggle dropdown ────────────────────────────────────────
  chip.addEventListener('click', function (e) {
    const isOpen = chip.classList.toggle('open');
    chip.setAttribute('aria-expanded', isOpen);
    e.stopPropagation();
  });
  document.addEventListener('click', () => {
    chip.classList.remove('open');
    chip.setAttribute('aria-expanded', 'false');
  });

  // Sign out
  chip.querySelector('#navSignOutBtn').addEventListener('click', (e) => {
    e.stopPropagation();
    localStorage.removeItem('userEmail');
    localStorage.removeItem('userName');
    window.location.href = 'login.html';
  });

  // ── Inject into desktop header-actions ────────────────────
  const headerActions = document.querySelector('.header-actions');
  if (headerActions) {
    headerActions.classList.add('nav-logged-in');
    // Insert chip before the hamburger button
    const hamburger = headerActions.querySelector('.hamburger');
    if (hamburger) {
      headerActions.insertBefore(chip, hamburger);
    } else {
      headerActions.appendChild(chip);
    }
  }

  // ── Replace mobile-auth section with profile row ───────────
  const mobileAuth = document.querySelector('.mobile-auth');
  if (mobileAuth) {
    mobileAuth.innerHTML = `
      <div class="mobile-profile-row">
        <div class="mobile-profile-avatar">${initials}</div>
        <div class="mobile-profile-info">
          <p>${name}</p>
          <p>${email}</p>
        </div>
      </div>
      <a href="profile.html"    class="mobile-nav-link" style="color:var(--emerald);">👤 My Profile</a>
      <button id="mobileSignOut" class="mobile-nav-link" style="border:none;background:none;width:100%;text-align:left;color:#fca5a5;cursor:pointer;">⟵ Sign Out</button>
    `;
    document.getElementById('mobileSignOut')?.addEventListener('click', () => {
      localStorage.removeItem('userEmail');
      localStorage.removeItem('userName');
      window.location.href = 'login.html';
    });
  }
})();

// ===== AUTH FORMS (login.html / signup.html) =====
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value.trim();
    const msgEl = document.getElementById('loginMessage');
    const btn = document.getElementById('loginBtn');

    if (!email || !password) {
      showAuthMessage(msgEl, 'Please fill in all fields.', 'error');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showAuthMessage(msgEl, 'Please enter a valid email address.', 'error');
      return;
    }
    if (password.length < 6) {
      showAuthMessage(msgEl, 'Password must be at least 6 characters.', 'error');
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Signing in...';

    setTimeout(() => {
      // Simulate auth — just store and redirect
      localStorage.setItem('userEmail', email.toLowerCase());
      localStorage.setItem('userName', email.split('@')[0]);
      showAuthMessage(msgEl, '✅ Login successful! Redirecting...', 'success');
      setTimeout(() => { window.location.href = 'index.html'; }, 1400);
    }, 900);
  });
}

const signupForm = document.getElementById('signupForm');
if (signupForm) {
  signupForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const name = document.getElementById('signupName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const phone = document.getElementById('signupPhone').value.trim();
    const nic = document.getElementById('signupNic').value.trim();
    const password = document.getElementById('signupPassword').value.trim();
    const confirm = document.getElementById('signupConfirm').value.trim();
    const msgEl = document.getElementById('signupMessage');
    const btn = document.getElementById('signupBtn');

    if (!name || !email || !phone || !nic || !password || !confirm) {
      showAuthMessage(msgEl, 'Please fill in all fields.', 'error'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showAuthMessage(msgEl, 'Please enter a valid email address.', 'error'); return;
    }
    if (password.length < 6) {
      showAuthMessage(msgEl, 'Password must be at least 6 characters.', 'error'); return;
    }
    if (!/[A-Z]/.test(password)) {
      showAuthMessage(msgEl, 'Password must contain at least one uppercase letter.', 'error'); return;
    }
    if (!/[0-9]/.test(password)) {
      showAuthMessage(msgEl, 'Password must contain at least one number.', 'error'); return;
    }
    if (password !== confirm) {
      showAuthMessage(msgEl, 'Passwords do not match.', 'error'); return;
    }

    btn.disabled = true;
    btn.textContent = 'Creating account...';

    setTimeout(() => {
      localStorage.setItem('userEmail', email.toLowerCase());
      localStorage.setItem('userName', name);
      showAuthMessage(msgEl, '✅ Account created! Redirecting...', 'success');
      setTimeout(() => { window.location.href = 'index.html'; }, 1400);
    }, 900);
  });
}

function showAuthMessage(el, text, type) {
  if (!el) return;
  el.textContent = text;
  el.className = `auth-message auth-message--${type}`;
  el.style.display = 'block';
}

// Demo login button
const demoBtn = document.getElementById('demoLoginBtn');
if (demoBtn) {
  demoBtn.addEventListener('click', () => {
    const emailEl = document.getElementById('loginEmail');
    const passEl = document.getElementById('loginPassword');
    if (emailEl) emailEl.value = 'demo@example.com';
    if (passEl) passEl.value = 'Demo1234';
    loginForm?.dispatchEvent(new Event('submit'));
  });
}