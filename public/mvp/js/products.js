import { Api, ApiError } from './api.js';
import { openModal, closeModal } from './components/modal.js';
import { renderTable, filterRows } from './components/table.js';

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
function fmtMoney(n) {
  return '$' + parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function productFormFields(product = {}) {
  return `
    <div class="form-grid">
      <div class="field"><label>SKU *</label><input type="text" name="sku" value="${escapeHtml(product.sku)}" required></div>
      <div class="field"><label>Nombre *</label><input type="text" name="name" value="${escapeHtml(product.name)}" required></div>
      <div class="field"><label>Precio *</label><input type="number" step="0.01" min="0" name="price" value="${product.price ?? ''}" required></div>
      <div class="field"><label>Stock</label><input type="number" min="0" name="stock" value="${product.stock ?? 0}"></div>
      <div class="field"><label>Descripción</label><input type="text" name="description" value="${escapeHtml(product.description)}"></div>
    </div>
  `;
}

export function initProductsView(container, currentUser) {
  container.innerHTML = `
    <div class="page-header">
      <h1>Productos</h1>
      <button type="button" class="btn-sm qa-btn" id="btn-new-product">+ Nuevo producto</button>
    </div>

    <div class="card">
      <div class="toolbar">
        <input type="search" id="product-search" placeholder="Buscar por SKU o nombre..." class="search-input">
      </div>
      <div id="products-table-wrap"></div>
    </div>
  `;

  const tableWrap = container.querySelector('#products-table-wrap');
  const searchInput = container.querySelector('#product-search');
  let allProducts = [];

  const columns = [
    { label: 'SKU', render: (p) => escapeHtml(p.sku) },
    { label: 'Nombre', render: (p) => escapeHtml(p.name) },
    { label: 'Precio', render: (p) => fmtMoney(p.price) },
    { label: 'Stock', render: (p) => p.stock },
    {
      label: 'Acciones',
      render: (p) => `
        <div class="actions">
          <button class="btn-sm" data-edit="${p.id}">Editar</button>
          <button class="btn-sm danger" data-delete="${p.id}" ${currentUser.role !== 'admin' ? 'disabled title="Solo un admin puede eliminar"' : ''}>Eliminar</button>
        </div>`,
    },
  ];

  function draw(rows) {
    tableWrap.innerHTML = renderTable({ columns, rows, emptyMessage: 'No hay productos todavía.' });
    tableWrap.querySelectorAll('[data-edit]').forEach((btn) => {
      btn.addEventListener('click', () => openFormModal(allProducts.find((p) => p.id == btn.dataset.edit)));
    });
    tableWrap.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', () => handleDelete(btn.dataset.delete));
    });
  }

  async function refresh() {
    tableWrap.innerHTML = renderTable({ columns, rows: [], emptyMessage: 'Cargando...' });
    try {
      const res = await Api.listProducts();
      allProducts = res.data || [];
      draw(filterRows(allProducts, searchInput.value, ['sku', 'name']));
    } catch (err) {
      handleViewError(err);
    }
  }

  searchInput.addEventListener('input', () => {
    draw(filterRows(allProducts, searchInput.value, ['sku', 'name']));
  });

  function handleViewError(err) {
    if (err instanceof ApiError && err.status === 401) {
      window.location.href = 'index.html';
      return;
    }
    tableWrap.innerHTML = `<div class="alert alert-error">Error al cargar: ${escapeHtml(err.message)}</div>`;
  }

  container.querySelector('#btn-new-product').addEventListener('click', () => openFormModal());

  function openFormModal(product) {
    const isEdit = !!product;
    openModal(`
      <h3>${isEdit ? 'Editar producto' : 'Nuevo producto'}</h3>
      <div id="form-alert"></div>
      <form id="product-form">
        ${productFormFields(product || {})}
        <div class="modal-actions">
          <button type="button" class="btn-sm" id="cancel-form">Cancelar</button>
          <button type="submit" class="btn-primary">${isEdit ? 'Guardar cambios' : 'Crear producto'}</button>
        </div>
      </form>
    `);
    document.getElementById('cancel-form').addEventListener('click', closeModal);
    document.getElementById('product-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = Object.fromEntries(fd.entries());
      try {
        if (isEdit) {
          await Api.updateProduct(product.id, payload);
        } else {
          await Api.createProduct(payload);
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
