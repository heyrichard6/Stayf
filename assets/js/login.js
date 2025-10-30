
function togglePasswordVisibility() {
  const passwordInput = document.getElementById('password');
  const togglePassword = document.getElementById('togglePassword');
  const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordInput.setAttribute('type', type);
  togglePassword.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');
  const btn  = document.getElementById('loginBtn');
  const msg  = document.getElementById('message');

  const setMsg = (text, ok=false) => {
    msg.textContent = text || '';
    msg.className = 'mt-4 text-center text-sm ' + (ok ? 'text-green-600' : 'text-red-600');
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg('');
    btn.disabled = true;
    btn.textContent = 'Signing in...';

    try {
      const res = await fetch('api/login.php', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'include'
      });

      const raw = await res.text();
      let data = null;
      try { data = JSON.parse(raw); } catch {}

      if (!res.ok || !data?.success) {
        setMsg(data?.message || raw || 'Invalid credentials');
        return;
      }

      // Redirect based on role
      const role = data.user?.role_name?.toLowerCase();
      if (['superadmin', 'admin', 'manager', 'encoder', 'staff'].includes(role)) {
        window.location.href = 'admin_dashboard.html';
      } else {
        window.location.href = 'dashboard.html';
      }
    } catch (err) {
      console.error(err);
      setMsg('Network/JS error: ' + (err?.message || err));
    } finally {
      btn.disabled = false;
      btn.textContent = 'Log In';
    }
  });
});
