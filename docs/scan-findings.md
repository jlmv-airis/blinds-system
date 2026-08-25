# Escaneo de código — Blindsystem ERP (V5.3)

Fecha: 2026-08-25
Fuente: `incoming/Blindsystem_V5.3/` (versión canónica; se comparó contra `Blindsystem_V5.2/`). `Blindsystem_V5.1.rar` no se descomprimió — no se auditó.

## Resumen del sistema

ERP web para fabricación/venta de persianas, cortinas y toldos. Marca operativa: **Lanson Shades (LS)**, más las marcas hermanas **Rollertex (RT)** y **Lanson Beckman (LB)** — es decir, el sistema es **multi-empresa** (`CCompany`), no de una sola marca. "Blindsystem" es el nombre nuevo del ERP en sí, pero "Lanson" sigue vivo como marca operativa dentro de los datos y del código (ver hallazgos §9 más abajo). Esto matiza lo que se dijo al inicio del proyecto — no es solo "se renombró Lanson a Blindsystem", es un ERP multi-marca donde Lanson sigue siendo una de las marcas activas.

## Stack confirmado

| Componente | Detalle |
|---|---|
| Backend | Laravel 8.83, PHP 7.3–8.0 |
| Frontend | Vue 2.7 + Vuetify 2.6, compilado con Laravel Mix 6 (Webpack) |
| Base de datos | MySQL 8.x, ~119 tablas (dump externo, no en migraciones) |
| Auth | Firebase (login) → JWT propio (`tymon/jwt-auth`) para la API |
| Tiempo real | Node.js `socket-server.js` standalone (puerto 3000) + Pusher configurado pero no conectado end-to-end |
| Almacenamiento | AWS S3 (configurado en `.env`, vacío en el ejemplo) |
| Exportación | Laravel Excel (Maatwebsite), FPDF |
| Integración externa | API ASPEL vía `http://aspelroller3.ddns.net:81` (sistema contable, host DDNS) |

## 🔴 Hallazgos críticos de seguridad (resolver antes de migrar)

1. **Endpoints sin autenticación que leen/escriben datos reales:**
   - `prefix('test')` en `routes/web.php` — dashboards y descarga de archivos sin ningún middleware.
   - `POST /import/importItemsOrder` — carga de archivos sin auth.
   - `prefix('sections')` — operaciones CRUD sin `jwt.auth`.
   - `/articles/prices`, `/articles/prices/update|delete|create` — **lectura y escritura directa de precios/SKUs** (`DB::table('c_articles')`) solo protegidos por sesión/CSRF, no por JWT.

2. **Credenciales reales expuestas en el código fuente:**
   - `.env` real (no el ejemplo) va incluido en el zip, con `APP_KEY` y `JWT_SECRET` reales — **deben rotarse**, nunca deben pasar al nuevo repo/entorno.
   - `app/classes/env` — archivo paralelo con credenciales de Firebird en texto plano para las 3 marcas (`sysdba` / `masterkey` — el password default de Firebird), más rutas locales a archivos `.fdb`. Si esas credenciales siguen vivas en algún servidor, hay que rotarlas.
   - `_check2.php` — credenciales MySQL hardcodeadas (`root`/`root`).
   - `test_jwt.php` — token JWT real hardcodeado.
   - `app/Console/Commands/CreateUsersFromTemplate.php` — 8 contraseñas en texto plano ligadas a correos reales de empleados.
   - Contraseña por defecto **`Blind001`** para todos los usuarios sembrados (documentado en `SETUP.md`).

3. **Archivos de debug en la raíz del proyecto** (`_debug_home.php`, `_check2.php`, `_test_login.php`, `test_jwt.php`): no están ruteados por Laravel y no son alcanzables si el document root del servidor apunta a `public/` — pero exponen PII, credenciales y JWTs si alguna vez se sirven mal. **Recomendación: eliminarlos antes de subir a Neubox**, sin importar la config del servidor.

4. **`socket-server.js`** — proceso Node aparte, sin autenticación, CORS abierto (`origin: '*'`), escuchando en el puerto 3000. Actualmente no parece estar conectado a lógica de negocio real — hay que decidir si se conserva, se asegura, o se retira.

## 🟡 Bloqueadores / pendientes antes de migrar

- **No existe el dump de base de datos en el zip.** `setup/SETUP.md` referencia `LansonAllDB.sql` (~67.7 MB, ~119 tablas) en una máquina Windows externa (`C:\Users\Soporte\Documents\...`) que **no se incluyó**. Sin esto no hay esquema/datos reales que migrar — hay que conseguirlo aparte.
- Los assets de Vue vienen **precompilados** en `public/v/0.3.16/` — el propio README advierte **no correr** `npm run dev/production` porque rompe el bundle (490KB vs 2.6MB esperado). Para la nube hay que decidir si se reconstruye el pipeline de build correctamente o se sigue shippeando el bundle precompilado.
- El esquema real de BD (119 tablas) no vive en migraciones de Laravel — solo hay 4 migraciones default. No hay versionado de esquema; toda la estructura depende del dump SQL externo.

## 🟢 Otros hallazgos relevantes

- **V5.2 → V5.3**: cambio incremental, no reescritura. Se agregó un módulo de "inventario local" (`CLocalInventoryController` + modelos), se tocaron rutas y el middleware CSRF, y se agregó un bloque grande (~480 líneas) de JS crudo dentro de `resources/views/app.blade.php` (CRUD de artículos/inventario hecho a mano, no como componente `.vue` normal) — acoplado a los endpoints sin auth de `/articles/prices/*`.
- **Bugs funcionales de marca** (no solo cosméticos): `EOrderController` asigna literalmente `'Lanson Shades'` a solicitudes de material sin importar la marca real; los PDFs generados (`FPDF.php` y variantes) siempre usan el logo/texto "Lanson Shades" sin importar si el pedido es de RT o LB; los correos de cotización tienen el asunto hardcodeado con "Lanson Shades"; los nombres de archivo de exportación a Excel también.
- Dominio adicional no documentado antes: `https://red.blindsystems.com/qr/` usado para generación de QR — confirmar si sigue vivo/es propiedad de la empresa.
- No se encontraron integraciones de pago, SMS, WhatsApp o paquetería.

## Próximos pasos sugeridos

1. Confirmar con el usuario: ¿quién tiene el dump `LansonAllDB.sql`? Es bloqueante para tener datos reales.
2. Decidir manejo de las 3 marcas (LS/RT/LB) en el ambiente cloud — ¿un solo despliegue multi-empresa como hoy, o se separan?
3. Rotar toda credencial expuesta (APP_KEY, JWT_SECRET, Firebird, MySQL) antes de exponer el sistema en Neubox.
4. Eliminar archivos de debug/test de la raíz.
5. Resolver los endpoints sin autenticación (§ Hallazgos críticos, punto 1) — especialmente los de precios.
6. Definir estrategia real de build de frontend (reparar pipeline Mix o seguir con bundle precompilado versionado).
7. Confirmar accesibilidad desde Neubox hacia `aspelroller3.ddns.net:81` (¿sigue existiendo? ¿hay VPN/whitelist?).

## Actualización — comparación V5.3 vs V5.4 (2026-08-25)

El usuario confirmó que `Blindsystem_5.4` es la versión vigente actualmente en producción. Se comparó a fondo contra V5.3 (código, rutas, config, dependencias, docs, `.env`, credenciales de Firebird):

**Resultado: V5.3 y V5.4 son idénticas byte a byte** en todo lo que se revisó (`app/`, `routes/`, `config/`, `database/migrations/`, `resources/`, `composer.json`, `package.json`, `SISTEMA.md`, `README.md`, `.env`, `app/classes/env`). Mismo conteo de archivos, mismos hashes agregados. Solo difieren permisos de archivo del `.env` (664 vs 644) — cosmético.

**Esto es raro dado que se esperaba que V5.4 fuera una versión más nueva.** Vale la pena confirmar con quien extrajo/copió el archivo que `Blindsystem_5.4` realmente viene del servidor de producción actual y no es una re-extracción accidental del mismo paquete V5.3. No bloquea el trabajo — todos los hallazgos de seguridad de V5.3 (rutas sin auth, scripts de debug, `socket-server.js` abierto, credenciales de Firebird en texto plano) aplican igual a lo que hoy está en producción.

**Hallazgo adicional en esta pasada:** el `.env` compartido por ambas versiones tiene `APP_ENV=local` y `APP_DEBUG=true` — si esto es real en el servidor de producción actual, es una mala práctica seria (debug activado en prod expone stack traces, rutas internas, etc.) y debe corregirse en la migración a Neubox sin importar el origen del archivo.

Con esto ya se puede pasar a diseñar el equipo de agentes y el plan de migración detallado.
