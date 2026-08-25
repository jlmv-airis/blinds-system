import { Api, ApiError } from './api.js';

// Si ya hay sesión válida, saltar directo al dashboard
if (Api.isAuthenticated()) {
  Api.me().then(() => { window.location.href = 'dashboard.html'; }).catch(() => Api.clearToken());
}

const form = document.getElementById('login-form');
const alertBox = document.getElementById('login-alert');
const submitBtn = document.getElementById('login-btn');

function showError(message) {
  alertBox.innerHTML = `<div class="alert alert-error">${message}</div>`;
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertBox.innerHTML = '';
  submitBtn.disabled = true;
  submitBtn.textContent = 'Iniciando sesión...';

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;

  try {
    const res = await Api.login(email, password);
    Api.setToken(res.token);
    window.location.href = 'dashboard.html';
  } catch (err) {
    if (err instanceof ApiError) {
      showError(err.message);
    } else {
      showError('Error inesperado al iniciar sesión.');
    }
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Iniciar sesión';
  }
});
