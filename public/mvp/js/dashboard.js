import { Api } from './api.js';
import { initModal } from './components/modal.js';
import { initHomeView } from './home.js';
import { initClientsView } from './clients.js';
import { initProductsView } from './products.js';
import { initOrdersView } from './orders.js';

// --- Guard de autenticación ---
if (!Api.isAuthenticated()) {
  window.location.href = 'index.html';
}

initModal();

// --- Sidebar colapsable ---
const sidebar = document.getElementById('sidebar');
const COLLAPSE_KEY = 'mvp_sidebar_collapsed';
if (localStorage.getItem(COLLAPSE_KEY) === '1') sidebar.classList.add('collapsed');

function toggleSidebar() {
  sidebar.classList.toggle('collapsed');
  localStorage.setItem(COLLAPSE_KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
}
document.getElementById('sidebar-toggle').addEventListener('click', toggleSidebar);
document.getElementById('sidebar-toggle-mobile').addEventListener('click', () => sidebar.classList.toggle('mobile-open'));

// --- Navegación de sidebar + breadcrumb ---
const views = {
  home: document.getElementById('view-home'),
  clients: document.getElementById('view-clients'),
  products: document.getElementById('view-products'),
  orders: document.getElementById('view-orders'),
};
const breadcrumb = document.getElementById('breadcrumb');

function setActiveView(target, title) {
  document.querySelectorAll('.nav-link[data-view]').forEach((l) => l.classList.remove('active'));
  const link = document.querySelector(`.nav-link[data-view="${target}"]`);
  if (link) link.classList.add('active');
  breadcrumb.innerHTML = target === 'home'
    ? '<span>Inicio</span>'
    : `<span class="breadcrumb-link" data-view="home">Inicio</span><span class="breadcrumb-sep">/</span><span>${title}</span>`;
  breadcrumb.querySelectorAll('.breadcrumb-link').forEach((el) => {
    el.addEventListener('click', () => goToView('home'));
  });
  Object.entries(views).forEach(([key, el]) => {
    el.hidden = key !== target;
  });
  sidebar.classList.remove('mobile-open');
}

function goToView(target) {
  const link = document.querySelector(`.nav-link[data-view="${target}"]`);
  if (link) setActiveView(target, link.dataset.title);
}

document.querySelectorAll('.nav-link[data-view]').forEach((link) => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    setActiveView(link.dataset.view, link.dataset.title);
  });
});

export { goToView };

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
  initHomeView(views.home, user);
  initClientsView(views.clients, user);
  initProductsView(views.products, user);
  initOrdersView(views.orders, user);
})();
