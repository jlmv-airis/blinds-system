import { Api, ApiError } from './api.js';
import { openModal, closeModal } from './components/modal.js';
import { renderTable } from './components/table.js';

const STATUS_LABEL = { pending: 'Pendiente', confirmed: 'Confirmada', cancelled: 'Cancelada' };

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
function fmtMoney(n) {
  return '$' + parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
}

export function initOrdersView(container, currentUser) {
  container.innerHTML = `
    <div class="page-header"><h1>Órdenes</h1></div>

    <div class="card">
      <h2>Nueva orden</h2>
      <div id="create-alert"></div>
      <form id="order-create-form">
        <div class="field" style="max-width:320px;">
          <label>Cliente *</label>
          <select name="client_id" id="order-client-select" required></select>
        </div>

        <label style="margin-top:10px;">Productos *</label>
        <div id="order-items-rows"></div>
        <button type="button" class="btn-sm" id="add-item-row" style="margin-top:6px;">+ Agregar producto</button>

        <div id="order-total-preview" style="margin:14px 0; font-size:15px; font-weight:600;">Total: $0.00</div>

        <button type="submit" class="btn-sm qa-btn">+ Crear orden</button>
      </form>
    </div>

    <div class="card">
      <h2>Listado</h2>
      <div class="toolbar">
        <input type="search" id="order-search" placeholder="Buscar por cliente..." class="search-input">
      </div>
      <div id="orders-table-wrap"></div>
    </div>
  `;

  const tableWrap = container.querySelector('#orders-table-wrap');
  const orderSearch = container.querySelector('#order-search');
  let allOrders = [];
  const createForm = container.querySelector('#order-create-form');
  const createAlert = container.querySelector('#create-alert');
  const clientSelect = container.querySelector('#order-client-select');
  const itemsRows = container.querySelector('#order-items-rows');
  const totalPreview = container.querySelector('#order-total-preview');

  let clientsCache = [];
  let productsCache = [];

  function productOptionsHtml(selected) {
    return productsCache.map((p) =>
      `<option value="${p.id}" data-price="${p.price}" ${p.id == selected ? 'selected' : ''}>${escapeHtml(p.sku)} — ${escapeHtml(p.name)} (${fmtMoney(p.price)})</option>`
    ).join('');
  }

  function addItemRow() {
    const row = document.createElement('div');
    row.className = 'form-grid';
    row.style.marginBottom = '8px';
    row.innerHTML = `
      <div class="field" style="margin-bottom:0;">
        <select class="item-product">${productOptionsHtml()}</select>
      </div>
      <div class="field" style="margin-bottom:0; max-width:100px;">
        <input type="number" class="item-qty" min="1" value="1" required>
      </div>
      <div style="flex:0; display:flex; align-items:center;">
        <button type="button" class="btn-sm danger remove-item-row">Quitar</button>
      </div>
    `;
    row.querySelector('.remove-item-row').addEventListener('click', () => {
      row.remove();
      recalcTotal();
    });
    row.querySelector('.item-product').addEventListener('change', recalcTotal);
    row.querySelector('.item-qty').addEventListener('input', recalcTotal);
    itemsRows.appendChild(row);
    recalcTotal();
  }

  function recalcTotal() {
    let total = 0;
    itemsRows.querySelectorAll('.form-grid').forEach((row) => {
      const sel = row.querySelector('.item-product');
      const qty = parseInt(row.querySelector('.item-qty').value, 10) || 0;
      const price = parseFloat(sel.options[sel.selectedIndex]?.dataset.price || 0);
      total += price * qty;
    });
    totalPreview.textContent = 'Total: ' + fmtMoney(total);
  }

  async function loadFormData() {
    const [clientsRes, productsRes] = await Promise.all([Api.listClients(), Api.listProducts()]);
    clientsCache = clientsRes.data || [];
    productsCache = productsRes.data || [];
    clientSelect.innerHTML = clientsCache.map((c) => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    itemsRows.innerHTML = '';
    addItemRow();
  }

  container.querySelector('#add-item-row').addEventListener('click', addItemRow);

  const columns = [
    { label: 'ID', render: (o) => `#${o.id}` },
    { label: 'Cliente', render: (o) => escapeHtml(o.client?.name || '—') },
    {
      label: 'Estado',
      render: (o) => `
        <select class="status-select" data-id="${o.id}" style="padding:4px 6px; font-size:12px; width:auto;">
          <option value="pending" ${o.status === 'pending' ? 'selected' : ''}>Pendiente</option>
          <option value="confirmed" ${o.status === 'confirmed' ? 'selected' : ''}>Confirmada</option>
          <option value="cancelled" ${o.status === 'cancelled' ? 'selected' : ''}>Cancelada</option>
        </select>`,
    },
    { label: 'Productos', render: (o) => o.items_count ?? '—' },
    { label: 'Total', render: (o) => fmtMoney(o.total) },
    { label: 'Fecha', render: (o) => fmtDate(o.created_at) },
    {
      label: 'Acciones',
      render: (o) => `
        <div class="actions">
          <button class="btn-sm" data-detail="${o.id}">Ver</button>
          <button class="btn-sm danger" data-delete="${o.id}" ${currentUser.role !== 'admin' ? 'disabled title="Solo un admin puede eliminar"' : ''}>Eliminar</button>
        </div>`,
    },
  ];

  function draw(rows) {
    tableWrap.innerHTML = renderTable({ columns, rows, emptyMessage: 'No hay órdenes todavía.' });
    tableWrap.querySelectorAll('.status-select').forEach((sel) => {
      sel.addEventListener('change', () => handleStatusChange(sel.dataset.id, sel.value));
    });
    tableWrap.querySelectorAll('[data-detail]').forEach((btn) => {
      btn.addEventListener('click', () => openDetailModal(btn.dataset.detail));
    });
    tableWrap.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', () => handleDelete(btn.dataset.delete));
    });
  }

  function applyFilter() {
    const q = orderSearch.value.trim().toLowerCase();
    const rows = q ? allOrders.filter((o) => (o.client?.name || '').toLowerCase().includes(q)) : allOrders;
    draw(rows);
  }
  orderSearch.addEventListener('input', applyFilter);

  async function refresh() {
    tableWrap.innerHTML = renderTable({ columns, rows: [], emptyMessage: 'Cargando...' });
    try {
      const res = await Api.listOrders();
      allOrders = res.data || [];
      applyFilter();
    } catch (err) {
      handleViewError(err);
    }
  }

  function handleViewError(err) {
    if (err instanceof ApiError && err.status === 401) {
      window.location.href = 'index.html';
      return;
    }
    tableWrap.innerHTML = `<div class="alert alert-error">Error al cargar: ${escapeHtml(err.message)}</div>`;
  }

  createForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    createAlert.innerHTML = '';

    const items = [...itemsRows.querySelectorAll('.form-grid')].map((row) => ({
      product_id: parseInt(row.querySelector('.item-product').value, 10),
      quantity: parseInt(row.querySelector('.item-qty').value, 10),
    }));

    const payload = { client_id: parseInt(clientSelect.value, 10), items };

    try {
      await Api.createOrder(payload);
      createAlert.innerHTML = `<div class="alert alert-success">Orden creada correctamente.</div>`;
      setTimeout(() => (createAlert.innerHTML = ''), 3000);
      await loadFormData();
      refresh();
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
      createAlert.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
    }
  });

  async function handleStatusChange(id, status) {
    try {
      await Api.updateOrder(id, { status });
      refresh();
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
      alert('No se pudo cambiar el estado: ' + err.message);
      refresh();
    }
  }

  async function openDetailModal(id) {
    try {
      const res = await Api.showOrder(id);
      const o = res.data;
      openModal(`
        <h3>Orden #${o.id}</h3>
        <p style="margin:0 0 4px;"><strong>Cliente:</strong> ${escapeHtml(o.client?.name || '—')}</p>
        <p style="margin:0 0 4px;"><strong>Email:</strong> ${escapeHtml(o.client?.email || '—')}</p>
        <p style="margin:0 0 14px;"><strong>Estado:</strong> ${STATUS_LABEL[o.status] || o.status}</p>
        <table>
          <thead><tr><th>Producto</th><th>Cant.</th><th>Precio unit.</th><th>Subtotal</th></tr></thead>
          <tbody>
            ${o.items.map((it) => `
              <tr>
                <td>${escapeHtml(it.product?.sku || '')} — ${escapeHtml(it.product?.name || '')}</td>
                <td>${it.quantity}</td>
                <td>${fmtMoney(it.unit_price)}</td>
                <td>${fmtMoney(it.subtotal)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <div style="text-align:right; font-weight:700; margin-top:10px;">Total: ${fmtMoney(o.total)}</div>
        <div class="modal-actions">
          <button type="button" class="btn-sm" id="close-detail">Cerrar</button>
        </div>
      `);
      document.getElementById('close-detail').addEventListener('click', closeModal);
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
      alert('No se pudo cargar el detalle: ' + err.message);
    }
  }

  async function handleDelete(id) {
    if (!confirm('¿Eliminar esta orden? Esta acción no se puede deshacer.')) return;
    try {
      await Api.deleteOrder(id);
      refresh();
    } catch (err) {
      if (err instanceof ApiError && (err.status === 403 || err.status === 401)) {
        if (err.status === 401) return (window.location.href = 'index.html');
        alert(err.message);
        return;
      }
      alert('No se pudo eliminar: ' + err.message);
    }
  }

  loadFormData().then(refresh);
}
