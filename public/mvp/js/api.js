/**
 * Cliente API del MVP — habla exclusivamente con /api/*, nunca con /auth/* (legacy).
 * Maneja token Sanctum, y normaliza 401/403/422 para que las vistas los consuman fácil.
 */

const API_BASE = '/api';
const TOKEN_KEY = 'mvp_token';

class ApiError extends Error {
  constructor(status, message, fieldErrors) {
    super(message);
    this.status = status;
    this.fieldErrors = fieldErrors || null; // { campo: ['mensaje', ...] } cuando es 422
  }
}

const Api = {
  getToken() {
    return localStorage.getItem(TOKEN_KEY);
  },
  setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
  },
  clearToken() {
    localStorage.removeItem(TOKEN_KEY);
  },
  isAuthenticated() {
    return !!this.getToken();
  },

  /**
   * @throws {ApiError} en cualquier respuesta no-2xx
   */
  async request(method, path, body) {
    const headers = { Accept: 'application/json' };
    const token = this.getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;
    if (body) headers['Content-Type'] = 'application/json';

    let res;
    try {
      res = await fetch(API_BASE + path, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
      });
    } catch (networkErr) {
      throw new ApiError(0, 'No se pudo conectar con el servidor. ¿Está corriendo el backend?');
    }

    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      // respuesta sin cuerpo JSON (ej. 204)
    }

    if (res.status === 401) {
      this.clearToken();
      throw new ApiError(401, 'Tu sesión expiró. Inicia sesión de nuevo.');
    }
    if (res.status === 403) {
      throw new ApiError(403, data?.message || 'No tienes permiso para esta acción.');
    }
    if (res.status === 422) {
      const fieldErrors = data?.errors || {};
      const firstMsg = Object.values(fieldErrors).flat()[0] || 'Datos inválidos.';
      throw new ApiError(422, firstMsg, fieldErrors);
    }
    if (res.status >= 400) {
      throw new ApiError(res.status, data?.message || 'Ocurrió un error inesperado.');
    }

    return data;
  },

  // --- Auth ---
  login(email, password) {
    return this.request('POST', '/login', { email, password });
  },
  logout() {
    return this.request('POST', '/logout').finally(() => this.clearToken());
  },
  me() {
    return this.request('GET', '/me');
  },

  // --- Clientes ---
  listClients() {
    return this.request('GET', '/clients');
  },
  createClient(data) {
    return this.request('POST', '/clients', data);
  },
  updateClient(id, data) {
    return this.request('PUT', `/clients/${id}`, data);
  },
  deleteClient(id) {
    return this.request('DELETE', `/clients/${id}`);
  },

  // --- Productos ---
  listProducts() {
    return this.request('GET', '/products');
  },
  createProduct(data) {
    return this.request('POST', '/products', data);
  },
  updateProduct(id, data) {
    return this.request('PUT', `/products/${id}`, data);
  },
  deleteProduct(id) {
    return this.request('DELETE', `/products/${id}`);
  },
};

export { Api, ApiError };
