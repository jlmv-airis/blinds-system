# Frontend del MVP — vanilla JS, sin build, solo `/api/*`

Reemplaza el prototipo de un solo archivo (`public/mvp.html`) por una estructura real. Conectado exclusivamente a la API Sanctum del MVP — cero referencias a `/auth/*` ni al frontend legacy.

## Decisión de arquitectura: vanilla JS con módulos, no Vue 3/React

El pipeline de build (Webpack/Mix) del proyecto legacy ya está roto (documentado en `docs/scan-findings.md` — el propio README dice "NO ejecutar npm run dev/production"), y no hay evidencia de que Node/npm compilen de forma confiable en este entorno. Un SPA con Vue 3/React necesitaría un paso de build (Vite) a correr localmente y luego subir el `dist/` a cPanel — un punto de falla adicional, difícil de depurar en hosting compartido. Vanilla JS con módulos ES (`import`/`export`) da la misma estructura y capacidad, **cero build step** — se sube tal cual por FTP, igual que ya se validó con `public/v/0.3.16/` (el bundle legacy también son solo archivos estáticos).

## Estructura de carpetas

```
public/mvp/
├── index.html          # login (punto de entrada — GET / redirige aquí)
├── dashboard.html       # shell: sidebar + áreas de contenido
├── css/
│   └── app.css           # todo el estilo, SaaS-simple (sidebar, tarjetas, modal)
└── js/
    ├── api.js             # cliente API — único lugar que sabe hablar con /api/*
    ├── auth.js             # lógica de la pantalla de login
    ├── dashboard.js         # shell: guard de auth, navegación sidebar, modal compartido, logout
    ├── clients.js            # vista + CRUD de clientes (import { openModal } from dashboard.js)
    └── products.js            # vista + CRUD de productos
```

## Flujo principal

1. `GET /` → redirige a `/mvp/index.html` (gracias al aislamiento del legacy hecho antes).
2. Login → `POST /api/login` → token Sanctum guardado en `localStorage`.
3. Redirección a `dashboard.html`.
4. `dashboard.js` valida el token contra `GET /api/me`; si falla, vuelve a `index.html` — así ninguna vista carga con una sesión inválida.
5. Sidebar cambia entre las vistas de Clientes/Productos sin recargar la página (ambas ya están montadas, solo se ocultan/muestran).

## Manejo de errores (401/403/422)

Centralizado en `api.js`, una sola vez — ninguna vista repite esta lógica:

| Código | Comportamiento |
|---|---|
| `401` | Se limpia el token y se redirige a `index.html` — "tu sesión expiró". Se revisa en cada vista antes de renderizar. |
| `403` | Se muestra el mensaje del backend tal cual (ej. al intentar eliminar sin ser admin) — no se limpia sesión, la acción simplemente se bloquea. |
| `422` | Se muestra el primer error de validación devuelto por Laravel, campo por campo — probado en vivo con nombre vacío y SKU duplicado. |
| Error de red | Mensaje explícito ("No se pudo conectar con el servidor") en vez de una excepción sin manejar. |

## Verificado en vivo (no solo leído)

- Login real con `admin@blinds-system.com` → token válido → dashboard carga.
- Crear cliente desde la UI → aparece en la tabla → persiste en MySQL (confirmado recargando).
- **Editar cliente vía modal** (reemplaza los `prompt()` del prototipo anterior) → guarda y refresca la tabla automáticamente.
- Botón "Eliminar" deshabilitado visualmente para `role=user`, habilitado para `role=admin` — el permiso real sigue estando en el backend (`middleware('admin')`), esto es solo UX.

## Cómo correrlo en local (Mac)

No hay paso de build. Con el backend ya corriendo (ver `docs/mvp.md`):

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir **http://127.0.0.1:8000/** — redirige solo a `/mvp/index.html`.

## Cómo conectarlo al backend

Ya está conectado — `js/api.js` usa `API_BASE = '/api'` (ruta relativa), así que sirve tanto en local (`http://127.0.0.1:8000/api/...`) como en Neubox (`https://blinds-system.com/api/...`) sin ningún cambio de configuración, siempre que el frontend se sirva desde el mismo dominio que el backend Laravel — que es el caso, ambos viven en el mismo `public/`.

## Explícitamente fuera de este entregable

- No se tocó `routes/api.php` ni ningún controlador — cero cambios de lógica de backend.
- No se creó ningún build tool (Vite/webpack) nuevo.
- No se reactivó ni se tocó el frontend legacy — sigue igual de aislado detrás de `LEGACY_UI_ENABLED`.
