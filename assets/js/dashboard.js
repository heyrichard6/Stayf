// stay/assets/js/dashboard.js
(function(){
  'use strict';

  const PLACEHOLDER = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><rect fill="#f3f4f6" width="100%" height="100%"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9ca3af" font-size="20">No image</text></svg>'
  );

  function computeAppBase(){
    const baseEl = document.querySelector('base[href]');
    if(baseEl){
      let href = baseEl.getAttribute('href') || '/';
      if(!href.endsWith('/')) href += '/';
      return href;
    }
    const parts = location.pathname.split('/');
    if(parts.length > 1 && parts[1]) return `/${parts[1]}/`;
    return '/';
  }
  const APP_BASE = computeAppBase();

  function getImageUrl(raw){
    if(!raw) return PLACEHOLDER;
    raw = String(raw).trim();
    if(/^https?:\/\//i.test(raw)) return raw;
    if(raw.startsWith('/')) {
      if(APP_BASE !== '/' && raw.startsWith(APP_BASE)) return raw;
      return APP_BASE.replace(/\/$/,'') + raw;
    }
    return APP_BASE + raw.replace(/^\/+/, '');
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Refs
    const grid = document.getElementById('units');
    const empty = document.getElementById('empty');
    const selLoc = document.getElementById('filterLocation');
    const minR = document.getElementById('filterMinPrice');
    const maxR = document.getElementById('filterMaxPrice');
    const minLbl = document.getElementById('minPriceLabel');
    const maxLbl = document.getElementById('maxPriceLabel');
    const addBtn = document.getElementById('addUnitBtn');

    // Notifications
    const notificationsBtn = document.getElementById('notificationsBtn');
    const notificationsBadge = document.getElementById('notificationsBadge');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationsList = document.getElementById('notificationsList');
    const notificationsEmpty = document.getElementById('notificationsEmpty');

    const modal = document.getElementById('unitModal');
    const modalTitle = document.getElementById('unitModalTitle');
    const modalClose = document.getElementById('unitModalClose');
    const form = document.getElementById('unitForm');
    const cancelBtn = document.getElementById('unitCancel');
    const submitBtn = document.getElementById('unitSubmit');

    const viewModal = document.getElementById('viewModal');
    const viewClose = document.getElementById('viewClose');
    const viewCloseBtn = document.getElementById('viewCloseBtn');
    const viewImg = document.getElementById('viewImg');
    const viewTitle = document.getElementById('viewTitle');
    const viewLocation = document.getElementById('viewLocation');
    const viewPrice = document.getElementById('viewPrice');
    const viewCapacity = document.getElementById('viewCapacity');
    const viewDesc = document.getElementById('viewDesc');
    const viewLocationLinkWrap = document.getElementById('viewLocationLinkWrap');
    const viewLocationLink = document.getElementById('viewLocationLink');
    const viewBookBtn = document.getElementById('viewBookBtn');

    const bookingModal = document.getElementById('bookingModal');
    const bookingForm = document.getElementById('bookingForm');
    const bookingClose = document.getElementById('bookingClose');
    const bookingCancel = document.getElementById('bookingCancel');
    const bookingSubmit = document.getElementById('bookingSubmit');
    const bookingRoomId = document.getElementById('bookingRoomId');
    const bookingStart = document.getElementById('bookingStart');
    const bookingEnd = document.getElementById('bookingEnd');
    const bookingGuests = document.getElementById('bookingGuests');
    const bookingError = document.getElementById('bookingError');

    const paymentModal = document.getElementById('paymentModal');
    const paymentForm = document.getElementById('paymentForm');
    const paymentClose = document.getElementById('paymentClose');
    const paymentCancel = document.getElementById('paymentCancel');
    const paymentSubmit = document.getElementById('paymentSubmit');
    const paymentBookingId = document.getElementById('paymentBookingId');
    const paymentError = document.getElementById('paymentError');

    // Approvals
    const openApprovalsBtn = document.getElementById('openApprovalsBtn');
    const approvalsModal   = document.getElementById('approvalsModal');
    const approvalsClose   = document.getElementById('approvalsClose');
    const approvalsList    = document.getElementById('approvalsList');
    const approvalsEmpty   = document.getElementById('approvalsEmpty');
    const approvalsError   = document.getElementById('approvalsError');
    const approvalsBadge   = document.getElementById('approvalsBadge');

    // state
    let allRooms = [];
    let canManage = false;
    window.currentRoomId = null;

    const show = el => el && el.classList && el.classList.remove('hidden');
    const hide = el => el && el.classList && el.classList.add('hidden');
    const escHtml = s => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const escAttr = s => String(s ?? '').replace(/"/g,'&quot;');

    // Logout
    document.getElementById('logoutBtn')?.addEventListener('click', async () => { try { await fetch('api/logout.php', { credentials:'include' }); } catch{} window.location.href = 'login.html'; });
    document.getElementById('logoutBtnMobile')?.addEventListener('click', async () => { try { await fetch('api/logout.php', { credentials:'include' }); } catch{} window.location.href = 'login.html'; });

    // Notifications
    function setupNotifications() {
      if (!notificationsBtn || !notificationsDropdown) return;

      // Toggle dropdown
      notificationsBtn.addEventListener('click', () => {
        notificationsDropdown.classList.toggle('hidden');
        loadNotifications();
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', (e) => {
        if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
          notificationsDropdown.classList.add('hidden');
        }
      });

      // Load notifications periodically
      refreshNotificationsBadge();
      setInterval(refreshNotificationsBadge, 60000);
    }

    async function loadNotifications() {
      try {
        const res = await fetch('api/notifications.php', { credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load notifications');

        const notifications = data.notifications || [];
        if (!notifications.length) {
          notificationsList.innerHTML = '';
          notificationsEmpty.classList.remove('hidden');
          return;
        }

        notificationsEmpty.classList.add('hidden');
        notificationsList.innerHTML = notifications.map(n => `
          <div class="p-3 border-b border-slate-100 hover:bg-slate-50 ${n.status === 'Unread' ? 'bg-blue-50/50' : ''}">
            <div class="text-sm">${escHtml(n.message)}</div>
            <div class="text-xs text-slate-500 mt-1">
              ${new Date(n.created_at).toLocaleString()}
            </div>
          </div>
        `).join('');

        // Mark notifications as read
        const unreadIds = notifications
          .filter(n => n.status === 'Unread')
          .map(n => n.notification_id);
        
        if (unreadIds.length) {
          for (const id of unreadIds) {
            await fetch('api/notifications.php', {
              method: 'POST',
              credentials: 'include',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `notification_id=${id}`
            });
          }
          refreshNotificationsBadge();
        }
      } catch (err) {
        console.error('Failed to load notifications:', err);
      }
    }

    async function refreshNotificationsBadge() {
      try {
        const res = await fetch('api/notifications.php', { credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error();
        
        const unreadCount = (data.notifications || []).filter(n => n.status === 'Unread').length;
        if (unreadCount > 0) {
          notificationsBadge?.classList.remove('hidden');
          notificationsBadge && (notificationsBadge.textContent = String(unreadCount));
        } else {
          notificationsBadge?.classList.add('hidden');
          notificationsBadge && (notificationsBadge.textContent = '0');
        }
      } catch { /* silent */ }
    }

    // Auth + load
    (async function init(){
      const r = await fetch('api/me.php', { credentials:'include' });
      const payload = await r.json().catch(()=>({}));
      const user = payload?.user || null;
      if (!user) {
        window.location.href = 'login.html';
        return;
      }
      // paint name/role
      const nameEl = document.getElementById('signedInName');
      const roleEl = document.getElementById('signedInRole');
      if (nameEl) nameEl.textContent = user.name?.trim() || user.email || 'User';
      if (roleEl) roleEl.textContent = user.role_name || '';

      window.userId = user.id;
      window.userRole = (user.role_name || '').toLowerCase();
      canManage = ['superadmin','admin','owner','manager'].includes(window.userRole);
      if (!canManage && addBtn) {
        addBtn.setAttribute('disabled', 'true');
        addBtn.classList.add('opacity-60');
      } else if (canManage && openApprovalsBtn) {
        openApprovalsBtn.classList.remove('hidden');
        refreshApprovalsBadge();
        // Optional auto refresh
        setInterval(() => refreshApprovalsBadge(), 60000);
      }

      // Setup notifications
      setupNotifications();

      await loadRooms();
    })().catch(()=>{});

    async function loadRooms(showAll = false) {
      hide(empty); if (grid) grid.innerHTML = '';
      const q = new URLSearchParams({
        location: selLoc?.value || '',
        min: minR?.value || '',
        max: maxR?.value || '',
        show_all: showAll ? '1' : '0'
      });
      try {
        const res = await fetch(`api/rooms_list.php?${q}`, { credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load rooms');
        allRooms = data.rooms || [];
        buildLocationFilter(allRooms);
        render();
      } catch (e) {
        if (empty) { empty.textContent = 'Failed to load rooms.'; show(empty); }
      }
    }

    function buildLocationFilter(rooms) {
      if (!selLoc) return;
      const unique = Array.from(new Set(rooms.map(r => (r.location || '').trim()).filter(Boolean))).sort((a,b)=>a.localeCompare(b));
      selLoc.innerHTML = '<option value="">All locations</option>' + unique.map(loc => `<option value="${escAttr(loc)}">${escHtml(loc)}</option>`).join('');
    }

    function render() {
      if (minLbl) minLbl.textContent = minR?.value ?? '';
      if (maxLbl) maxLbl.textContent = maxR?.value ?? '';

      const min = Number(minR?.value || 0);
      const max = Number(maxR?.value || Infinity);
      const loc = selLoc?.value || '';

      const list = allRooms.filter(r => {
        const price = r.price == null ? null : Number(r.price);
        const okLoc = loc === '' || (r.location || '') === loc;
        const okPrice = price == null || (price >= min && price <= max);
        return okLoc && okPrice;
      });

      // Show all units regardless of status when "Units" is clicked
      // The API now returns all rooms, so we don't filter by availability here

      if (!list.length) {
        if (grid) grid.innerHTML = '';
        if (empty) { empty.textContent = 'No units found.'; show(empty); }
        return;
      }
      hide(empty);

      grid.innerHTML = list.map(r => {
        const title = r.title || '(Untitled)';
        const loc = r.location || '';
        const price = r.price != null ? Number(r.price) : '';
        const cap = r.capacity != null ? r.capacity : '';
        const imgSrc = getImageUrl(r.image || '');
        const id = r.id;
        const isBooked = r.is_booked == 1;
        const statusText = isBooked ? 'Booked' : 'Available';
        const statusColor = isBooked ? 'text-red-600' : 'text-green-600';

        const isOwner = window.userRole === 'owner' && r.owner_id == window.userId;
        const canEditDelete = canManage && (window.userRole === 'superadmin' || window.userRole === 'admin' || isOwner);

        return `
          <div class="bg-white/80 backdrop-blur-md border border-white/30 rounded-xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-sky-200/20" data-id="${id}">
            ${imgSrc ? `<img src="${escAttr(imgSrc)}" data-original="${escAttr(imgSrc)}" alt="${escAttr(title)}" class="h-40 w-full object-cover" onerror="this.onerror=null; this.src='${escAttr(PLACEHOLDER)}';">` : `<div class="h-40 w-full bg-gray-200"></div>`}
            <div class="p-4">
              <h3 class="font-semibold text-gray-900">${escHtml(title)}</h3>
              <p class="text-sm text-gray-600">${escHtml(loc)}</p>
              <div class="mt-2 flex items-center justify-between text-sm text-gray-700">
                <span>${price !== '' ? '₱ ' + price.toLocaleString() : ''}</span>
                <span>${cap !== '' ? cap + ' pax' : ''}</span>
              </div>
              <div class="mt-1 text-xs ${statusColor} font-medium">${statusText}</div>
              <div class="mt-3 grid gap-2 ${canEditDelete ? 'grid-cols-3' : 'grid-cols-2'}">
                <button data-view="${id}" class="px-3 py-2 rounded bg-blue-700 text-white text-sm hover:bg-blue-800">View</button>
                ${canEditDelete ? `
                  <button data-edit="${id}" class="px-3 py-2 rounded border text-sm hover:bg-gray-50">Edit</button>
                  <button data-del="${id}" class="px-3 py-2 rounded border text-sm hover:bg-red-50">Delete</button>
                ` : isBooked ? `<button disabled class="px-3 py-2 rounded bg-gray-400 text-white text-sm cursor-not-allowed">Booked</button>` : `<button data-book="${id}" class="px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700">Book Now</button>`}
              </div>
            </div>
          </div>
        `;
      }).join('');

      grid.querySelectorAll('[data-view]').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openView(btn.dataset.view); }));
      grid.querySelectorAll('[data-book]').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openBooking(btn.dataset.book); }));
      if (canManage) {
        grid.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openEdit(btn.dataset.edit); }));
        grid.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); doDelete(btn.dataset.del); }));
      }
      grid.querySelectorAll('.unit-card').forEach(card => card.addEventListener('click', () => openView(card.dataset.id)));
    }

    [selLoc, minR, maxR].forEach(el => el && el.addEventListener('input', render));
    addBtn?.addEventListener('click', () => openCreate());
    modalClose?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    function openCreate(){
      if (!form) return;
      modalTitle.textContent = 'Add Unit';
      form.reset();
      form.id.value = '';
      form.existing_image_path.value = '';
      submitBtn.textContent = 'Create';
      modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function openEdit(id){
      const r = allRooms.find(x=>String(x.id)===String(id)); if(!r) return;
      modalTitle.textContent='Edit Unit'; form.reset();
      form.id.value=r.id||''; 
      form.title.value=r.title||''; 
      form.location.value=r.location||''; 
      form.capacity.value=r.capacity||''; 
      form.price_per_night.value=r.price ?? '';
      form.existing_image_path.value=r.image||''; 
      form.description.value=r.description||''; 
      try{ form.querySelector('[name="location_link"]').value=r.location_link||''; }catch(e){}
      submitBtn.textContent='Update'; 
      modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function closeModal(){ modal?.classList.add('hidden'); modal?.classList.remove('flex'); }

    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.disabled = true;
      const fd = new FormData(form);
      const isEdit = !!fd.get('id');
      const url = isEdit ? 'api/room_update.php' : 'api/room_create.php';
      try {
        const res = await fetch(url, { method: 'POST', body: fd, credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message||'Save failed');
        closeModal(); await loadRooms();
      } catch (err) {
        alert(err.message||err);
      } finally {
        submitBtn.disabled = false;
      }
    });

    async function doDelete(id){
      if(!confirm('Delete this unit?')) return;
      try {
        const fd = new FormData(); fd.append('id', id);
        const res = await fetch('api/room_delete.php', { method:'POST', body:fd, credentials:'include' });
        const data = await res.json();
        if(!data.success) throw new Error(data.message||'Delete failed');
        await loadRooms();
      } catch(e){ alert(e.message||e); }
    }

    function openView(id){
      const r = allRooms.find(x=>String(x.id)===String(id)); if(!r) return;
      if(viewImg) viewImg.src = getImageUrl(r.image || '');
      viewTitle && (viewTitle.textContent = r.title || '(Untitled)');
      viewLocation && (viewLocation.textContent = r.location ? `📍 ${r.location}` : '');
      viewPrice && (viewPrice.textContent = (r.price != null) ? `₱ ${Number(r.price).toLocaleString()}` : '');
      viewCapacity && (viewCapacity.textContent = (r.capacity != null) ? `• ${r.capacity} pax` : '');
      viewDesc && (viewDesc.textContent = r.description || '');
      window.currentRoomId = r.id;
      if (viewModal) viewModal.dataset.roomId = r.id;
      if (r.location_link && /^https?:\/\//i.test(r.location_link)) { if(viewLocationLink){ viewLocationLink.href = r.location_link; show(viewLocationLinkWrap); } }
      else { if(viewLocationLink){ viewLocationLink.href = '#'; hide(viewLocationLinkWrap); } }
      viewModal?.classList.remove('hidden'); viewModal?.classList.add('flex');
    }

    function closeView(){ window.currentRoomId = null; if(viewModal) viewModal.dataset.roomId = ''; viewModal?.classList.add('hidden'); viewModal?.classList.remove('flex'); }
    viewClose?.addEventListener('click', closeView);
    viewCloseBtn?.addEventListener('click', closeView);
    viewModal?.addEventListener('click', (e)=>{ if(e.target===viewModal) closeView(); });

    window.openBooking = function(roomId, defaultStart=null, defaultEnd=null){
      if(!bookingRoomId) return;
      bookingRoomId.value = roomId;
      if(bookingStart) bookingStart.value = defaultStart || '';
      if(bookingEnd) bookingEnd.value = defaultEnd || '';
      if(bookingGuests) bookingGuests.value = 1;
      bookingForm?.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
      bookingForm?.querySelector('textarea') && (bookingForm.querySelector('textarea').value = '');
      bookingError && bookingError.classList.add('hidden');
      bookingModal?.classList.remove('hidden'); bookingModal?.classList.add('flex');
      window.currentRoomId = roomId;
    };

    function closeBooking(){ bookingModal?.classList.add('hidden'); bookingModal?.classList.remove('flex'); }
    bookingClose?.addEventListener('click', closeBooking);
    bookingCancel?.addEventListener('click', closeBooking);
    bookingModal?.addEventListener('click', (e)=>{ if(e.target===bookingModal) closeBooking(); });

    function openPaymentModal(id){
      if(!paymentBookingId) return;
      paymentBookingId.value = id;
      paymentForm?.reset();
      paymentError?.classList.add('hidden');
      paymentModal?.classList.remove('hidden'); paymentModal?.classList.add('flex');
    }
    window.openPaymentModal = openPaymentModal;

    function closePayment(){ paymentModal?.classList.add('hidden'); paymentModal?.classList.remove('flex'); }
    paymentClose?.addEventListener('click', closePayment);
    paymentCancel?.addEventListener('click', closePayment);
    paymentModal?.addEventListener('click', e=>{ if(e.target===paymentModal) closePayment(); });

    bookingForm?.addEventListener('submit', async e => {
      e.preventDefault();
      try {
        // Create form data
        const fd = new FormData();
        
        // Add booking data
        fd.append('room_id', bookingRoomId.value);
        fd.append('checkin', bookingStart.value);
        fd.append('checkout', bookingEnd.value);
        fd.append('guests', bookingGuests.value);
        fd.append('paymentMethod', document.getElementById('bookingPaymentMethod').value || 'bank_transfer');
        
        // Get extras
        const extras = [];
        bookingForm.querySelectorAll('input[name="extras[]"]:checked').forEach(cb => {
          extras.push(cb.value);
        });
        fd.append('extras', JSON.stringify(extras));
        fd.append('notes', document.getElementById('bookingNotes').value || '');
        
        const res = await fetch('api/bookings.php', { method: 'POST', body: fd, credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Booking failed');
        
        closeBooking();
        alert('Booking successful! The admin will review your booking and payment method.');
      } catch (err) {
        bookingError && (bookingError.textContent = err.message || err);
        bookingError?.classList.remove('hidden');
      }
    });

    // Book Now handler robustness
    (function attachViewBookNow(){
      const attempt = () => {
        const b = document.getElementById('viewBookBtn');
        if(!b) return setTimeout(attempt, 150);
        b.addEventListener('click', (e)=>{ 
          e.preventDefault(); 
          const id = window.currentRoomId || (viewModal && viewModal.dataset && viewModal.dataset.roomId) || null; 
          if(!id){ alert('Room not selected.'); return; } 
          closeView(); 
          openBooking(id); 
        });
      };
      attempt();
    })();

    // Filters reload
    [selLoc, minR, maxR].forEach(el => el && el.addEventListener('change', loadRooms));

    // Units button click handler
    const unitsBtn = document.getElementById('unitsBtn');
    if (unitsBtn) {
      unitsBtn.addEventListener('click', () => {
        // Scroll to units section
        const unitsSection = document.getElementById('units');
        if (unitsSection) {
          unitsSection.scrollIntoView({ behavior: 'smooth' });
        }
        // Reload rooms to show all (including booked ones)
        loadRooms(true);
      });
    }

    /* ===========================
       Approvals (Admin/Manager)
       =========================== */

    openApprovalsBtn?.addEventListener('click', async () => {
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
      if (!canManage) return;
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
          const title = (b.room_title || '(Untitled)').replace(/</g,'&lt;');
          const dates = `${b.start_date} → ${b.end_date}`;
          const amount = (b.total_amount != null) ? `₱ ${Number(b.total_amount).toLocaleString()}` : '';
          const user = (b.user_name || b.user_email || 'Guest').replace(/</g,'&lt;');
          const payMethod = b.payment_method || '';
          return `
            <div class="rounded-xl border p-3 space-y-2">
              <div class="flex items-start justify-between">
                <div class="min-w-0">
                  <div class="font-medium">${title}</div>
                  <div class="text-sm text-slate-600">${dates} • ${amount}</div>
                </div>
                <div class="text-sm text-slate-500">Requester: <span class="font-medium">${user}</span></div>
              </div>
              <div class="text-sm text-slate-600">Payment method: <span class="font-medium">${escHtml(payMethod)}</span></div>
              <div class="flex items-center gap-2 justify-end">
                <button data-approve="${b.id}" class="px-3 py-1.5 rounded-full bg-emerald-600 text-white text-sm hover:bg-emerald-700">Confirm</button>
                <button data-reject="${b.id}" class="px-3 py-1.5 rounded-full border text-sm hover:bg-slate-50">Cancel</button>
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
