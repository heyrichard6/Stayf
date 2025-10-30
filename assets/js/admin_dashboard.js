(function(){
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    // Refs
    const totalRooms = document.getElementById('totalRooms');
    const totalBookings = document.getElementById('totalBookings');
    const pendingApprovals = document.getElementById('pendingApprovals');
    const activeUsers = document.getElementById('activeUsers');
    const recentBookings = document.getElementById('recentBookings');
    const approvalsBtn = document.getElementById('approvalsBtn');
    const approvalsModal = document.getElementById('approvalsModal');
    const approvalsClose = document.getElementById('approvalsClose');
    const approvalsList = document.getElementById('approvalsList');
    const approvalsEmpty = document.getElementById('approvalsEmpty');
    const approvalsError = document.getElementById('approvalsError');
    const approvalsBadge = document.getElementById('approvalsBadge');

    // Logout
    document.getElementById('logoutBtn')?.addEventListener('click', async () => {
      try { await fetch('api/logout.php', { credentials:'include' }); } catch{}
      window.location.href = 'login.html';
    });
    document.getElementById('logoutBtnMobile')?.addEventListener('click', async () => {
      try { await fetch('api/logout.php', { credentials:'include' }); } catch{}
      window.location.href = 'login.html';
    });

    // Auth + load
    (async function init(){
      const r = await fetch('api/me.php', { credentials:'include' });
      const payload = await r.json().catch(()=>({}));
      const user = payload?.user || null;
      if (!user) {
        window.location.href = 'login.html';
        return;
      }
      // Check if admin
      const role = (user.role_name || '').toLowerCase();
      if (!['superadmin','admin','manager','staff','encoder'].includes(role)) {
        window.location.href = 'dashboard.html';
        return;
      }
      // paint name/role
      const nameEl = document.getElementById('signedInName');
      const roleEl = document.getElementById('signedInRole');
      if (nameEl) nameEl.textContent = user.name?.trim() || user.email || 'User';
      if (roleEl) roleEl.textContent = user.role_name || '';

      // Show Masterfile link only for superadmin
      const masterfileLink = document.querySelector('a[href="masterfile.html"]');
      if (masterfileLink) {
        if (role === 'superadmin') {
          masterfileLink.style.display = 'inline-flex';
        } else {
          masterfileLink.style.display = 'none';
        }
      }

      await loadDashboardData();
      await refreshApprovalsBadge();
      // Optional auto refresh
      setInterval(() => refreshApprovalsBadge(), 60000);
    })().catch(()=>{});

    async function loadDashboardData() {
      try {
        const res = await fetch('api/get_dashboard_data.php', { credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load data');

        if (totalRooms) totalRooms.textContent = data.total_rooms || 0;
        if (totalBookings) totalBookings.textContent = data.total_bookings || 0;
        if (pendingApprovals) pendingApprovals.textContent = data.pending_approvals || 0;

        // For active users, we might need a separate API, for now set to 0 or fetch from another endpoint
        if (activeUsers) activeUsers.textContent = 'N/A'; // Placeholder

        // Recent bookings
        const bookings = data.recent_bookings || [];
        if (recentBookings) {
          if (bookings.length === 0) {
            recentBookings.innerHTML = '<p class="text-slate-500">No recent bookings.</p>';
          } else {
            recentBookings.innerHTML = bookings.map(b => `
              <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200">
                <div>
                  <p class="font-medium">${b.guest_name || 'Unknown'}</p>
                  <p class="text-sm text-slate-600">${b.room_name || 'Unknown Room'}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm">${new Date(b.booking_date).toLocaleDateString()}</p>
                  <p class="text-xs text-slate-500">${b.status || 'Unknown'}</p>
                </div>
              </div>
            `).join('');
          }
        }
      } catch (e) {
        console.error('Error loading dashboard data:', e);
        if (recentBookings) recentBookings.innerHTML = '<p class="text-slate-500">Failed to load data.</p>';
      }
    }

    // Approvals
    approvalsBtn?.addEventListener('click', async () => {
      approvalsError?.classList.add('hidden');
      approvalsModal?.classList.remove('hidden');
      approvalsModal?.classList.add('flex');
      await loadPendingApprovals();
    });
    approvalsClose?.addEventListener('click', () => {
      approvalsModal?.classList.add('hidden');
      approvalsModal?.classList.remove('flex');
    });
    approvalsModal?.addEventListener('click', (e) => {
      if (e.target === approvalsModal) {
        approvalsModal?.classList.add('hidden');
        approvalsModal?.classList.remove('flex');
      }
    });

    async function loadPendingApprovals(){
      approvalsList.innerHTML = '';
      approvalsEmpty?.classList.add('hidden');

      try {
        const res = await fetch('api/bookings_list_pending.php', { credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load approvals');

        const items = data.bookings || [];
        if (!items.length) {
          approvalsEmpty?.classList.remove('hidden');
          approvalsBadge?.classList.add('hidden');
          approvalsBadge && (approvalsBadge.textContent = '0');
          return;
        }

        approvalsBadge?.classList.remove('hidden');
        approvalsBadge && (approvalsBadge.textContent = String(items.length));

        approvalsList.innerHTML = items.map(b => {
          const title = (b.room_title || '(Untitled)').replace(/</g,'<');
          const dates = `${b.start_date} → ${b.end_date}`;
          const pax   = `${b.guests || 1} guest${(b.guests||1)>1?'s':''}`;
          return `
            <div class="rounded-xl border p-3 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="font-medium">${title}</div>
                <div class="text-sm text-slate-600">${dates} • ${pax}</div>
              </div>
              <div class="flex items-center gap-2">
                <button data-approve="${b.id}" class="px-3 py-1.5 rounded-full bg-emerald-600 text-white text-sm hover:bg-emerald-700">Approve</button>
                <button data-reject="${b.id}" class="px-3 py-1.5 rounded-full border text-sm hover:bg-slate-50">Reject</button>
              </div>
            </div>
          `;
        }).join('');

        approvalsList.querySelectorAll('[data-approve]').forEach(btn => {
          btn.addEventListener('click', () => updateBookingStatus(btn.dataset.approve, 'approve'));
        });
        approvalsList.querySelectorAll('[data-reject]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const reason = prompt('Reason for rejection? (optional)');
            await updateBookingStatus(btn.dataset.reject, 'reject', reason || '');
          });
        });
      } catch (err) {
        approvalsError && (approvalsError.textContent = err.message || String(err));
        approvalsError?.classList.remove('hidden');
      }
    }

    async function refreshApprovalsBadge(){
      try {
        const res = await fetch('api/bookings_list_pending.php', { credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error();
        const n = (data.bookings || []).length;
        if (n > 0) {
          approvalsBadge?.classList.remove('hidden');
          approvalsBadge && (approvalsBadge.textContent = String(n));
        } else {
          approvalsBadge?.classList.add('hidden');
          approvalsBadge && (approvalsBadge.textContent = '0');
        }
      } catch { /* silent */ }
    }

    async function updateBookingStatus(id, action, reason=''){
      const fd = new FormData();
      fd.append('booking_id', id);
      fd.append('action', action);
      if (action === 'reject') fd.append('reason', reason);

      try {
        const res = await fetch('api/booking_update_status.php', { method:'POST', body: fd, credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to update');

        await loadPendingApprovals();
        await refreshApprovalsBadge();
      } catch (err) {
        alert(err.message || String(err));
      }
    }
  });
})();
