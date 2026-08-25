import { Api, ApiError } from './api.js';
import { openModal, closeModal } from './dashboard.js';

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

export function initClientsView(container, currentUser) {
  container.innerHTML = `
    <div class="page-header"><h1>Clientes</h1></div>

    <div class="card">
      <h2>Nuevo cliente</h2>
      <div id="create-alert"></div>
      <form id="client-create-form">
        <div class="form-grid">
          <div class="field">
            <label>Nombre *</label>
            <input type="text" name="name" required>
          </div>
          <div class="field">
            <label>Email</label>
            <input type="email" name="email">
          </div>
          <div class="field">
            <label>Teléfono</label>
            <input type="text" name="phone">
          </div>
          <div class="field">
            <label>Dirección</label>
            <input type="text" name="address">
          </div>
        </div>
        <button type="submit" class="btn-sm" style="background:var(--accent);color:#fff;margin-top:8px;">+ Agregar cliente</button>
      </form>
    </div>

    <div class="card">
      <h2>Listado</h2>
      <table>
        <thead><tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Dirección</th><th>Acciones</th></tr></thead>
        <tbody id="clients-tbody"></tbody>
      </table>
    </div>
  `;

  const tbody = container.querySelector('#clients-tbody');
  const createForm = container.querySelector('#client-create-form');
  const createAlert = container.querySelector('#create-alert');

  async function refresh() {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">Cargando...</td></tr>`;
    try {
      const res = await Api.listClients();
      renderRows(res.data);
    } catch (err) {
      handleViewError(err);
    }
  }

  function renderRows(clients) {
    if (!clients.length) {
      tbody.innerHTML = `<tr class="empty-row"><td colspan="5">No hay clientes todavía.</td></tr>`;
      return;
    }
    tbody.innerHTML = clients.map((c) => `
      <tr>
        <td>${escapeHtml(c.name)}</td>
        <td>${escapeHtml(c.email) || '—'}</td>
        <td>${escapeHtml(c.phone) || '—'}</td>
        <td>${escapeHtml(c.address) || '—'}</td>
        <td class="actions">
          <button class="btn-sm" data-edit="${c.id}">Editar</button>
          <button class="btn-sm danger" data-delete="${c.id}" ${currentUser.role !== 'admin' ? 'disabled title="Solo un admin puede eliminar"' : ''}>Eliminar</button>
        </td>
      </tr>
    `).join('');

    tbody.querySelectorAll('[data-edit]').forEach((btn) => {
      btn.addEventListener('click', () => openEditModal(clients.find((c) => c.id == btn.dataset.edit)));
    });
    tbody.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', () => handleDelete(btn.dataset.delete));
    });
  }

  function handleViewError(err) {
    if (err instanceof ApiError && err.status === 401) {
      window.location.href = 'index.html';
      return;
    }
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">Error al cargar: ${escapeHtml(err.message)}</td></tr>`;
  }

  createForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    createAlert.innerHTML = '';
    const fd = new FormData(createForm);
    const payload = Object.fromEntries(fd.entries());
    try {
      await Api.createClient(payload);
      createForm.reset();
      createAlert.innerHTML = `<div class="alert alert-success">Cliente creado correctamente.</div>`;
      setTimeout(() => (createAlert.innerHTML = ''), 3000);
      refresh();
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
      createAlert.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
    }
  });

  function openEditModal(client) {
    openModal(`
      <h3>Editar cliente</h3>
      <div id="edit-alert"></div>
      <form id="client-edit-form">
        <div class="field"><label>Nombre *</label><input type="text" name="name" value="${escapeHtml(client.name)}" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="${escapeHtml(client.email)}"></div>
        <div class="field"><label>Teléfono</label><input type="text" name="phone" value="${escapeHtml(client.phone)}"></div>
        <div class="field"><label>Dirección</label><input type="text" name="address" value="${escapeHtml(client.address)}"></div>
        <div class="modal-actions">
          <button type="button" class="btn-sm" id="cancel-edit">Cancelar</button>
          <button type="submit" class="btn-primary">Guardar cambios</button>
        </div>
      </form>
    `);
    document.getElementById('cancel-edit').addEventListener('click', closeModal);
    document.getElementById('client-edit-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = Object.fromEntries(fd.entries());
      try {
        await Api.updateClient(client.id, payload);
        closeModal();
        refresh();
      } catch (err) {
        if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
        document.getElementById('edit-alert').innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
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
