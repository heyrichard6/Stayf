// assets/js/settings.js
document.addEventListener('DOMContentLoaded', () => {
  const hotelForm = document.getElementById('hotelSettingsForm');
  const adminForm = document.getElementById('adminForm');
  const addAdminBtn = document.getElementById('addAdminBtn');
  const viewAdminsBtn = document.getElementById('viewAdminsBtn');
  const masterFileBtn = document.getElementById('masterFileBtn');
  const adminModal = document.getElementById('adminModal');
  const adminsListModal = document.getElementById('adminsListModal');
  const closeModal = document.getElementById('closeModal');
  const closeAdminsList = document.getElementById('closeAdminsList');
  const adminsList = document.getElementById('adminsList');

  // Load hotel settings
  loadHotelSettings();

  // Check user role and show/hide master file button
  checkUserRole();

  // Hotel settings form
  hotelForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = hotelForm.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    btn.disabled = true;

    try {
      const data = {
        hotel_name: document.getElementById('hotel_name').value,
        contact_email: document.getElementById('contact_email').value,
        contact_phone: document.getElementById('contact_phone').value,
        address: document.getElementById('address').value,
        tax_rate: parseFloat(document.getElementById('tax_rate').value) || 0
      };

      const res = await fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
        credentials: 'include'
      });

      const result = await res.json();
      if (result.success) {
        alert('Settings saved successfully!');
      } else {
        alert('Error: ' + (result.message || 'Failed to save settings'));
      }
    } catch (err) {
      console.error(err);
      alert('Network error: ' + err.message);
    } finally {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  });

  // Admin management
  addAdminBtn.addEventListener('click', () => {
    adminModal.classList.remove('hidden');
  });

  viewAdminsBtn.addEventListener('click', () => {
    loadAdminsList();
    adminsListModal.classList.remove('hidden');
  });

  closeModal.addEventListener('click', () => {
    adminModal.classList.add('hidden');
    adminForm.reset();
  });

  closeAdminsList.addEventListener('click', () => {
    adminsListModal.classList.add('hidden');
  });

  // Admin form submission
  adminForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = adminForm.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
    btn.disabled = true;

    try {
      const formData = new FormData(adminForm);
      const data = Object.fromEntries(formData);

      const res = await fetch('api/admin_register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
        credentials: 'include'
      });

      const result = await res.json();
      if (result.success) {
        alert('Admin account created successfully!');
        adminModal.classList.add('hidden');
        adminForm.reset();
      } else {
        alert('Error: ' + (result.message || 'Failed to create admin account'));
      }
    } catch (err) {
      console.error(err);
      alert('Network error: ' + err.message);
    } finally {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  });

  async function loadHotelSettings() {
    try {
      const res = await fetch('api/settings.php', { credentials: 'include' });
      const result = await res.json();
      if (result.success && result.settings) {
        const s = result.settings;
        document.getElementById('hotel_name').value = s.hotel_name || '';
        document.getElementById('contact_email').value = s.contact_email || '';
        document.getElementById('contact_phone').value = s.contact_phone || '';
        document.getElementById('address').value = s.address || '';
        document.getElementById('tax_rate').value = s.tax_rate || 0;
      }
    } catch (err) {
      console.error('Failed to load settings:', err);
    }
  }

  async function loadAdminsList() {
    try {
      const res = await fetch('api/admin_list.php', { credentials: 'include' });
      const result = await res.json();
      if (result.success) {
        adminsList.innerHTML = result.admins.map(admin => `
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-start">
              <div>
                <h4 class="font-semibold text-gray-800">${admin.name}</h4>
                <p class="text-sm text-gray-600">Username: ${admin.username}</p>
                <p class="text-sm text-gray-600">Email: ${admin.email || 'N/A'}</p>
                <p class="text-sm text-gray-600">Role: ${admin.role}</p>
                <p class="text-sm text-gray-600">Status: ${admin.status}</p>
                <p class="text-sm text-gray-500">Created: ${new Date(admin.created_at).toLocaleDateString()}</p>
              </div>
              <div class="flex space-x-2">
                <button class="text-blue-600 hover:text-blue-800" onclick="editAdmin(${admin.admin_id})">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="text-red-600 hover:text-red-800" onclick="deleteAdmin(${admin.admin_id})">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        `).join('');
      } else {
        adminsList.innerHTML = '<p class="text-gray-500">Failed to load admins list</p>';
      }
    } catch (err) {
      console.error('Failed to load admins:', err);
      adminsList.innerHTML = '<p class="text-gray-500">Error loading admins list</p>';
    }
  }

  async function checkUserRole() {
    try {
      const res = await fetch('api/me.php', { credentials: 'include' });
      const result = await res.json();
      if (result.success && result.user) {
        if (result.user.role === 'SuperAdmin') {
          masterFileBtn.style.display = 'block';
          masterFileBtn.addEventListener('click', () => {
            window.location.href = 'masterfile.html';
          });
        }
      }
    } catch (err) {
      console.error('Failed to check user role:', err);
    }
  }
});

// Global functions for admin actions
function editAdmin(adminId) {
  alert('Edit functionality coming soon for admin ID: ' + adminId);
}

function deleteAdmin(adminId) {
  if (confirm('Are you sure you want to delete this admin account?')) {
    alert('Delete functionality coming soon for admin ID: ' + adminId);
  }
}
