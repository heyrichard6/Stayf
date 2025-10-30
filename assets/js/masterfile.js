(function(){
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    // Refs
    const usersTab = document.getElementById('usersTab');
    const adminsTab = document.getElementById('adminsTab');
    const ownersTab = document.getElementById('ownersTab');
    const staffTab = document.getElementById('staffTab');
    const masterfileContent = document.getElementById('masterfileContent');
    const userModal = document.getElementById('userModal');
    const userModalTitle = document.getElementById('userModalTitle');
    const userModalClose = document.getElementById('userModalClose');
    const userForm = document.getElementById('userForm');
    const userCancel = document.getElementById('userCancel');
    const userSubmit = document.getElementById('userSubmit');

    let currentTab = 'users';

    // Auth check
    (async function init(){
      const r = await fetch('api/me.php', { credentials:'include' });
      const payload = await r.json().catch(()=>({}));
      const user = payload?.user || null;
      if (!user) {
        window.location.href = 'login.html';
        return;
      }
      // Check if superadmin
      const role = (user.role_name || '').toLowerCase();
      if (role !== 'superadmin') {
        window.location.href = 'admin_dashboard.html';
        return;
      }
      // paint name/role
      const nameEl = document.getElementById('signedInName');
      const roleEl = document.getElementById('signedInRole');
      if (nameEl) nameEl.textContent = user.name?.trim() || user.email || 'User';
      if (roleEl) roleEl.textContent = user.role_name || '';

      await loadTab(currentTab);
    })().catch(()=>{});

    // Tabs
    usersTab?.addEventListener('click', () => switchTab('users'));
    adminsTab?.addEventListener('click', () => switchTab('admins'));
    ownersTab?.addEventListener('click', () => switchTab('owners'));
    staffTab?.addEventListener('click', () => switchTab('staff'));

    function switchTab(tab) {
      currentTab = tab;
      [usersTab, adminsTab, ownersTab, staffTab].forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('hover:bg-slate-100');
      });
      const activeBtn = document.getElementById(tab + 'Tab');
      activeBtn.classList.add('bg-blue-600', 'text-white');
      activeBtn.classList.remove('hover:bg-slate-100');
      loadTab(tab);
    }

    async function loadTab(tab) {
      masterfileContent.innerHTML = '<p class="text-slate-500">Loading...</p>';
      try {
        let endpoint = '';
        if (tab === 'users') endpoint = 'api/get_users.php';
        else if (tab === 'admins') endpoint = 'api/get_admins.php';
        else if (tab === 'owners') endpoint = 'api/get_owners.php';
        else if (tab === 'staff') endpoint = 'api/get_staff.php';

        const res = await fetch(endpoint, { credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load data');

        const items = data.users || data.admins || data.owners || data.staff || [];
        if (items.length === 0) {
          masterfileContent.innerHTML = '<p class="text-slate-500">No data found.</p>';
          return;
        }

        const table = document.createElement('table');
        table.className = 'w-full text-sm';
        let headers = '';
        let rows = '';

        if (tab === 'staff') {
          headers = `
            <tr class="border-b">
              <th class="text-left py-2">Name</th>
              <th class="text-left py-2">Position</th>
              <th class="text-left py-2">Contact Number</th>
              <th class="text-left py-2">Status</th>
              <th class="text-left py-2">Actions</th>
            </tr>
          `;
          rows = items.map(item => `
            <tr class="border-b">
              <td class="py-2">${item.name || 'N/A'}</td>
              <td class="py-2">${item.position || 'N/A'}</td>
              <td class="py-2">${item.contact_number || 'N/A'}</td>
              <td class="py-2">${item.status || 'N/A'}</td>
              <td class="py-2">
                <button class="text-blue-600 hover:underline" onclick="editUser(${item.id})">Edit</button>
                <button class="text-red-600 hover:underline ml-2" onclick="deleteUser(${item.id})">Delete</button>
              </td>
            </tr>
          `).join('');
        } else {
          headers = `
            <tr class="border-b">
              <th class="text-left py-2">Name</th>
              <th class="text-left py-2">Email</th>
              <th class="text-left py-2">Role</th>
              <th class="text-left py-2">Actions</th>
            </tr>
          `;
          rows = items.map(item => `
            <tr class="border-b">
              <td class="py-2">${item.name || item.full_name || 'N/A'}</td>
              <td class="py-2">${item.email}</td>
              <td class="py-2">${item.role_name || item.role || 'N/A'}</td>
              <td class="py-2">
                <button class="text-blue-600 hover:underline" onclick="editUser(${item.id})">Edit</button>
                <button class="text-red-600 hover:underline ml-2" onclick="deleteUser(${item.id})">Delete</button>
              </td>
            </tr>
          `).join('');
        }

        table.innerHTML = `
          <thead>${headers}</thead>
          <tbody>${rows}</tbody>
        `;
        masterfileContent.innerHTML = '';
        masterfileContent.appendChild(table);
      } catch (e) {
        masterfileContent.innerHTML = '<p class="text-slate-500">Failed to load data.</p>';
        console.error(e);
      }
    }

    // Modal
    userModalClose?.addEventListener('click', () => userModal.classList.add('hidden'));
    userCancel?.addEventListener('click', () => userModal.classList.add('hidden'));
    userModal?.addEventListener('click', (e) => {
      if (e.target === userModal) userModal.classList.add('hidden');
    });

    userForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(userForm);
      try {
        const res = await fetch('api/register.php', { method:'POST', body: fd, credentials:'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to save');
        userModal.classList.add('hidden');
        await loadTab(currentTab);
      } catch (err) {
        alert(err.message || String(err));
      }
    });

    // Global functions for edit/delete
    window.editUser = (id) => {
      // Implement edit logic
      alert('Edit functionality not implemented yet');
    };

    window.deleteUser = (id) => {
      // Implement delete logic
      alert('Delete functionality not implemented yet');
    };
  });
})();
