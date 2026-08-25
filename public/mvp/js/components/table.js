/**
 * Tabla ERP reutilizable.
 * columns: [{ label, render(row) => htmlString, width? }]
 * rows: array de objetos de datos
 * options.emptyMessage: texto cuando rows está vacío
 * options.rowClass(row) => string opcional
 */
export function renderTable({ columns, rows, emptyMessage = 'Sin registros.', rowClass }) {
  const thead = `<thead><tr>${columns.map((c) => `<th${c.width ? ` style="width:${c.width}"` : ''}>${c.label}</th>`).join('')}</tr></thead>`;

  if (!rows || !rows.length) {
    return `<table>${thead}<tbody><tr class="empty-row"><td colspan="${columns.length}">${emptyMessage}</td></tr></tbody></table>`;
  }

  const body = rows.map((row) => {
    const cls = rowClass ? rowClass(row) : '';
    return `<tr${cls ? ` class="${cls}"` : ''}>${columns.map((c) => `<td>${c.render(row)}</td>`).join('')}</tr>`;
  }).join('');

  return `<table>${thead}<tbody>${body}</tbody></table>`;
}

/** Filtra rows por texto libre contra los campos indicados en `fields`. */
export function filterRows(rows, query, fields) {
  if (!query) return rows;
  const q = query.toLowerCase();
  return rows.filter((row) => fields.some((f) => String(row[f] ?? '').toLowerCase().includes(q)));
}
