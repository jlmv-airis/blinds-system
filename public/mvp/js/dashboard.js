import { Api } from './api.js';
import { initClientsView } from './clients.js';
import { initProductsView } from './products.js';

// --- Guard de autenticación ---
if (!Api.isAuthenticated()) {
  window.location.href = 'index.html';
}

// --- Modal compartido (usado por clients.js / products.js) ---
const overlay = document.getElementById('modal-overlay');
const modalBox = document.getElementById('modal-box');

export function openModal(html) {
  modalBox.innerHTML = html;
  overlay.hidden = false;
}
export function closeModal() {
  overlay.hidden = true;
  modalBox.innerHTML = '';
}
overlay.addEventListener('click', (e) => {
  if (e.target === overlay) closeModal();
});

// --- Navegación de sidebar ---
const views = {
  clients: document.getElementById('view-clients'),
  products: document.getElementById('view-products'),
};
document.querySelectorAll('.nav-link[data-view]').forEach((link) => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const target = link.dataset.view;
    document.querySelectorAll('.nav-link[data-view]').forEach((l) => l.classList.remove('active'));
    link.classList.add('active');
    Object.entries(views).forEach(([key, el]) => {
      el.hidden = key !== target;
    });
  });
});

// --- Logout ---
document.getElementById('logout-link').addEventListener('click', async (e) => {
  e.preventDefault();
  await Api.logout().catch(() => {}); // si el token ya expiró, igual limpiamos localmente
  window.location.href = 'index.html';
});

// --- Info del usuario actual ---
async function loadCurrentUser() {
  try {
    const res = await Api.me();
    document.getElementById('user-name').textContent = res.user.name;
    document.getElementById('user-role').textContent = res.user.role;
    return res.user;
  } catch (err) {
    window.location.href = 'index.html';
  }
}

(async function init() {
  const user = await loadCurrentUser();
  if (!user) return;
  initClientsView(views.clients, user);
  initProductsView(views.products, user);
})();
