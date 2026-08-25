import { Api, ApiError } from './api.js';
import { openModal, closeModal } from './components/modal.js';
import { renderTable, filterRows } from './components/table.js';

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function clientFormFields(client = {}) {
  return `
    <div class="form-grid">
      <div class="field"><label>Nombre *</label><input type="text" name="name" value="${escapeHtml(client.name)}" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="${escapeHtml(client.email)}"></div>
      <div class="field"><label>Teléfono</label><input type="text" name="phone" value="${escapeHtml(client.phone)}"></div>
      <div class="field"><label>Dirección</label><input type="text" name="address" value="${escapeHtml(client.address)}"></div>
    </div>
  `;
}

export function initClientsView(container, currentUser) {
  container.innerHTML = `
    <div class="page-header">
      <h1>Clientes</h1>
      <button type="button" class="btn-sm qa-btn" id="btn-new-client">+ Nuevo cliente</button>
    </div>

    <div class="card">
      <div class="toolbar">
        <input type="search" id="client-search" placeholder="Buscar por nombre, email o teléfono..." class="search-input">
      </div>
      <div id="clients-table-wrap"></div>
    </div>
  `;

  const tableWrap = container.querySelector('#clients-table-wrap');
  const searchInput = container.querySelector('#client-search');
  let allClients = [];

  const columns = [
    { label: 'Nombre', render: (c) => escapeHtml(c.name) },
    { label: 'Email', render: (c) => escapeHtml(c.email) || '—' },
    { label: 'Teléfono', render: (c) => escapeHtml(c.phone) || '—' },
    { label: 'Dirección', render: (c) => escapeHtml(c.address) || '—' },
    {
      label: 'Acciones',
      render: (c) => `
        <div class="actions">
          <button class="btn-sm" data-edit="${c.id}">Editar</button>
          <button class="btn-sm danger" data-delete="${c.id}" ${currentUser.role !== 'admin' ? 'disabled title="Solo un admin puede eliminar"' : ''}>Eliminar</button>
        </div>`,
    },
  ];

  function draw(rows) {
    tableWrap.innerHTML = renderTable({ columns, rows, emptyMessage: 'No hay clientes todavía.' });
    tableWrap.querySelectorAll('[data-edit]').forEach((btn) => {
      btn.addEventListener('click', () => openFormModal(allClients.find((c) => c.id == btn.dataset.edit)));
    });
    tableWrap.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', () => handleDelete(btn.dataset.delete));
    });
  }

  async function refresh() {
    tableWrap.innerHTML = renderTable({ columns, rows: [], emptyMessage: 'Cargando...' });
    try {
      const res = await Api.listClients();
      allClients = res.data || [];
      draw(filterRows(allClients, searchInput.value, ['name', 'email', 'phone']));
    } catch (err) {
      handleViewError(err);
    }
  }

  searchInput.addEventListener('input', () => {
    draw(filterRows(allClients, searchInput.value, ['name', 'email', 'phone']));
  });

  function handleViewError(err) {
    if (err instanceof ApiError && err.status === 401) {
      window.location.href = 'index.html';
      return;
    }
    tableWrap.innerHTML = `<div class="alert alert-error">Error al cargar: ${escapeHtml(err.message)}</div>`;
  }

  container.querySelector('#btn-new-client').addEventListener('click', () => openFormModal());

  function openFormModal(client) {
    const isEdit = !!client;
    openModal(`
      <h3>${isEdit ? 'Editar cliente' : 'Nuevo cliente'}</h3>
      <div id="form-alert"></div>
      <form id="client-form">
        ${clientFormFields(client || {})}
        <div class="modal-actions">
          <button type="button" class="btn-sm" id="cancel-form">Cancelar</button>
          <button type="submit" class="btn-primary">${isEdit ? 'Guardar cambios' : 'Crear cliente'}</button>
        </div>
      </form>
    `);
    document.getElementById('cancel-form').addEventListener('click', closeModal);
    document.getElementById('client-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = Object.fromEntries(fd.entries());
      try {
        if (isEdit) {
          await Api.updateClient(client.id, payload);
        } else {
          await Api.createClient(payload);
        }
        closeModal();
        refresh();
      } catch (err) {
        if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
        document.getElementById('form-alert').innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
      }
    });
  }

  async function handleDelete(id) {
    if (!confirm('¿Eliminar este cliente?')) return;
    try {
      await Api.deleteClient(id);
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

  refresh();
}
