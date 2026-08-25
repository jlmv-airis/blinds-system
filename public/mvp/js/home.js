import { Api, ApiError } from './api.js';

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
function fmtMoney(n) {
  return '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
        <div class="stat-label">Órdenes totales</div>
        <div class="stat-value" id="stat-orders">—</div>
        <a href="#" data-view="orders" class="nav-link stat-link">Ver órdenes →</a>
      </div>
      <div class="stat-card">
        <div class="stat-label">Ventas confirmadas</div>
        <div class="stat-value" id="stat-sales" style="font-size:24px;">—</div>
        <span class="hint-text" style="margin:0;">Suma de órdenes con estado "Confirmada"</span>
      </div>
    </div>

    <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom:18px;">
      <div class="stat-card" style="border-top-color: var(--warning);">
        <div class="stat-label">Pendientes</div>
        <div class="stat-value" id="stat-pending" style="font-size:22px;">—</div>
      </div>
      <div class="stat-card" style="border-top-color: var(--success);">
        <div class="stat-label">Confirmadas</div>
        <div class="stat-value" id="stat-confirmed" style="font-size:22px;">—</div>
      </div>
      <div class="stat-card" style="border-top-color: var(--error);">
        <div class="stat-label">Canceladas</div>
        <div class="stat-value" id="stat-cancelled" style="font-size:22px;">—</div>
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
      <button type="button" class="btn-sm qa-btn" data-view="orders">+ Nueva orden</button>
    </div>

    <div class="card">
      <h2>Actividad reciente</h2>
      <p class="hint-text">No existe un endpoint de actividad en la API — se arma con los registros más recientes de <code>/api/clients</code>, <code>/api/products</code> y <code>/api/orders</code> combinados.</p>
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
      const [clientsRes, productsRes, ordersRes] = await Promise.all([
        Api.listClients(), Api.listProducts(), Api.listOrders(),
      ]);
      const clients = clientsRes.data || [];
      const products = productsRes.data || [];
      const orders = ordersRes.data || [];

      container.querySelector('#stat-clients').textContent = clients.length;
      container.querySelector('#stat-products').textContent = products.length;
      container.querySelector('#stat-orders').textContent = orders.length;

      const pending = orders.filter((o) => o.status === 'pending');
      const confirmed = orders.filter((o) => o.status === 'confirmed');
      const cancelled = orders.filter((o) => o.status === 'cancelled');
      const sales = confirmed.reduce((sum, o) => sum + parseFloat(o.total || 0), 0);

      container.querySelector('#stat-pending').textContent = pending.length;
      container.querySelector('#stat-confirmed').textContent = confirmed.length;
      container.querySelector('#stat-cancelled').textContent = cancelled.length;
      container.querySelector('#stat-sales').textContent = fmtMoney(sales);

      const recent = [
        ...clients.map((c) => ({ type: 'Cliente', label: c.name, at: c.created_at })),
        ...products.map((p) => ({ type: 'Producto', label: `${p.sku} — ${p.name}`, at: p.created_at })),
        ...orders.map((o) => ({ type: 'Orden', label: `#${o.id} — ${o.client?.name || ''} (${fmtMoney(o.total)})`, at: o.created_at })),
      ]
        .sort((a, b) => new Date(b.at) - new Date(a.at))
        .slice(0, 8);

      const typeClass = { Cliente: 'client', Producto: 'product', Orden: 'order' };
      const list = container.querySelector('#activity-list');
      if (!recent.length) {
        list.innerHTML = `<li class="empty-row-inline">Sin registros todavía.</li>`;
      } else {
        list.innerHTML = recent.map((r) => `
          <li>
            <span class="activity-type activity-type--${typeClass[r.type]}">${r.type}</span>
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
