import { Api, ApiError } from './api.js';
import { openModal, closeModal } from './dashboard.js';

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

export function initProductsView(container, currentUser) {
  container.innerHTML = `
    <div class="page-header"><h1>Productos</h1></div>

    <div class="card">
      <h2>Nuevo producto</h2>
      <div id="create-alert"></div>
      <form id="product-create-form">
        <div class="form-grid">
          <div class="field"><label>SKU *</label><input type="text" name="sku" required></div>
          <div class="field"><label>Nombre *</label><input type="text" name="name" required></div>
          <div class="field"><label>Precio *</label><input type="number" step="0.01" min="0" name="price" required></div>
          <div class="field"><label>Stock</label><input type="number" min="0" name="stock" value="0"></div>
          <div class="field"><label>Descripción</label><input type="text" name="description"></div>
        </div>
        <button type="submit" class="btn-sm" style="background:var(--accent);color:#fff;margin-top:8px;">+ Agregar producto</button>
      </form>
    </div>

    <div class="card">
      <h2>Listado</h2>
      <table>
        <thead><tr><th>SKU</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
        <tbody id="products-tbody"></tbody>
      </table>
    </div>
  `;

  const tbody = container.querySelector('#products-tbody');
  const createForm = container.querySelector('#product-create-form');
  const createAlert = container.querySelector('#create-alert');

  async function refresh() {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">Cargando...</td></tr>`;
    try {
      const res = await Api.listProducts();
      renderRows(res.data);
    } catch (err) {
      handleViewError(err);
    }
  }

  function renderRows(products) {
    if (!products.length) {
      tbody.innerHTML = `<tr class="empty-row"><td colspan="5">No hay productos todavía.</td></tr>`;
      return;
    }
    tbody.innerHTML = products.map((p) => `
      <tr>
        <td>${escapeHtml(p.sku)}</td>
        <td>${escapeHtml(p.name)}</td>
        <td>$${parseFloat(p.price).toFixed(2)}</td>
        <td>${p.stock}</td>
        <td class="actions">
          <button class="btn-sm" data-edit="${p.id}">Editar</button>
          <button class="btn-sm danger" data-delete="${p.id}" ${currentUser.role !== 'admin' ? 'disabled title="Solo un admin puede eliminar"' : ''}>Eliminar</button>
        </td>
      </tr>
    `).join('');

    tbody.querySelectorAll('[data-edit]').forEach((btn) => {
      btn.addEventListener('click', () => openEditModal(products.find((p) => p.id == btn.dataset.edit)));
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
      await Api.createProduct(payload);
      createForm.reset();
      createAlert.innerHTML = `<div class="alert alert-success">Producto creado correctamente.</div>`;
      setTimeout(() => (createAlert.innerHTML = ''), 3000);
      refresh();
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
      createAlert.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
    }
  });

  function openEditModal(product) {
    openModal(`
      <h3>Editar producto</h3>
      <div id="edit-alert"></div>
      <form id="product-edit-form">
        <div class="field"><label>SKU *</label><input type="text" name="sku" value="${escapeHtml(product.sku)}" required></div>
        <div class="field"><label>Nombre *</label><input type="text" name="name" value="${escapeHtml(product.name)}" required></div>
        <div class="field"><label>Precio *</label><input type="number" step="0.01" min="0" name="price" value="${product.price}" required></div>
        <div class="field"><label>Stock</label><input type="number" min="0" name="stock" value="${product.stock}"></div>
        <div class="field"><label>Descripción</label><input type="text" name="description" value="${escapeHtml(product.description)}"></div>
        <div class="modal-actions">
          <button type="button" class="btn-sm" id="cancel-edit">Cancelar</button>
          <button type="submit" class="btn-primary">Guardar cambios</button>
        </div>
      </form>
    `);
    document.getElementById('cancel-edit').addEventListener('click', closeModal);
    document.getElementById('product-edit-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = Object.fromEntries(fd.entries());
      try {
        await Api.updateProduct(product.id, payload);
        closeModal();
        refresh();
      } catch (err) {
        if (err instanceof ApiError && err.status === 401) return (window.location.href = 'index.html');
        document.getElementById('edit-alert').innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
      }
    });
  }

  async function handleDelete(id) {
    if (!confirm('¿Eliminar este producto?')) return;
    try {
      await Api.deleteProduct(id);
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
