import { Api, ApiError } from './api.js';

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function fmtDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
}

function goToView(viewName) {
  const link = document.querySelector(`.nav-link[data-view="${viewName}"]`);
  if (link) link.click();
}

export function initHomeView(container, currentUser) {
  container.innerHTML = `
    <div class="page-header"><h1>Dashboard</h1></div>

    <div id="home-alert"></div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-label">Clientes activos</div>
        <div class="stat-value" id="stat-clients">—</div>
        <a href="#" data-view="clients" class="nav-link stat-link">Ver clientes →</a>
      </div>
      <div class="stat-card">
        <div class="stat-label">Productos activos</div>
        <div class="stat-value" id="stat-products">—</div>
        <a href="#" data-view="products" class="nav-link stat-link">Ver productos →</a>
      </div>
      <div class="stat-card">
        <div class="stat-label">Sesión actual</div>
        <div class="stat-value" style="font-size:16px;">${escapeHtml(currentUser.name)}</div>
        <span class="pill-role">${escapeHtml(currentUser.role)}</span>
      </div>
    </div>

    <div class="quick-access">
      <button type="button" class="btn-sm qa-btn" data-view="clients">+ Nuevo cliente</button>
      <button type="button" class="btn-sm qa-btn" data-view="products">+ Nuevo producto</button>
    </div>

    <div class="card">
      <h2>Actividad reciente</h2>
      <p class="hint-text">No existe un endpoint de actividad en la API — se arma con los registros más recientes de <code>/api/clients</code> y <code>/api/products</code> combinados.</p>
      <ul class="activity-list" id="activity-list">
        <li class="empty-row-inline">Cargando...</li>
      </ul>
    </div>
  `;

  // Los botones/enlaces con data-view dentro de esta vista navegan usando el sidebar real,
  // así no se duplica la lógica de cambio de vista que ya vive en dashboard.js
  container.querySelectorAll('[data-view]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      goToView(el.dataset.view);
    });
  });

  async function refresh() {
    const alertBox = container.querySelector('#home-alert');
    alertBox.innerHTML = '';
    try {
      const [clientsRes, productsRes] = await Promise.all([Api.listClients(), Api.listProducts()]);
      const clients = clientsRes.data || [];
      const products = productsRes.data || [];

      container.querySelector('#stat-clients').textContent = clients.length;
      container.querySelector('#stat-products').textContent = products.length;

      const recent = [
        ...clients.map((c) => ({ type: 'Cliente', label: c.name, at: c.created_at })),
        ...products.map((p) => ({ type: 'Producto', label: `${p.sku} — ${p.name}`, at: p.created_at })),
      ]
        .sort((a, b) => new Date(b.at) - new Date(a.at))
        .slice(0, 6);

      const list = container.querySelector('#activity-list');
      if (!recent.length) {
        list.innerHTML = `<li class="empty-row-inline">Sin registros todavía.</li>`;
      } else {
        list.innerHTML = recent.map((r) => `
          <li>
            <span class="activity-type activity-type--${r.type === 'Cliente' ? 'client' : 'product'}">${r.type}</span>
            <span class="activity-label">${escapeHtml(r.label)}</span>
            <span class="activity-date">${fmtDate(r.at)}</span>
          </li>
        `).join('');
      }
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        window.location.href = 'index.html';
        return;
      }
      alertBox.innerHTML = `<div class="alert alert-error">No se pudo cargar el resumen: ${escapeHtml(err.message)}</div>`;
    }
  }

  refresh();
  return { refresh };
}
