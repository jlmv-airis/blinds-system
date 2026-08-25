# Resumen de lo realizado — Migración Blindsystem a Neubox

Última actualización: 2026-08-25

## 1. Setup del proyecto

- Repo creado: [`jlmv-airis/blinds-system`](https://github.com/jlmv-airis/blinds-system) (privado), en `~/Documents/GitHub/blinds-system` — deliberadamente fuera de Google Drive para evitar corrupción de `.git` por sincronización.
- `gh` CLI instalado y autenticado; git configurado para usarlo como credential helper.
- Contexto inicial: ERP de venta de persianas/cortinas/toldos, empresa anterior **Lanson**, empresa actual **Blindsystem**.

## 2. Escaneo del código

- Recibido en `incoming/`: versiones V5.1 (sin descomprimir), V5.2, V5.3, y **5.4** (confirmada por el usuario como la vigente en producción).
- Hallazgo: V5.3 y "5.4" resultaron **idénticas byte a byte** — se le hizo notar al usuario, quien confirmó igual seguir con 5.4 como la real.
- Stack confirmado: **Laravel 8.83 (PHP 7.3–8.0 según `composer.json`, pero el `vendor/` real requiere PHP ≥8.3)** + **Vue 2.7 / Vuetify 2.6** (Laravel Mix 6) + **MySQL 8** (~119 tablas) + **Node.js** (`socket-server.js`, Socket.io) + **Firebase** (login) → **JWT** (`tymon/jwt-auth`) para la API.
- Es un ERP **multi-marca**: una sola base de datos sirve a Lanson Shades (LS), Rollertex (RT) y Lanson Beckman (LB) vía `CCompany` — "Lanson" no desapareció, sigue siendo una marca activa dentro de Blindsystem.
- Documentado en [`docs/scan-findings.md`](scan-findings.md).

### Hallazgos críticos de seguridad (escaneo inicial)
- Endpoints sin autenticación (`/articles/prices/*`, `/import/*`, `/sections/*`, grupo `test`) — algunos con lectura/escritura real de datos.
- Credenciales reales en el código: `.env` con `APP_KEY`/`JWT_SECRET` reales, `app/classes/env` con credenciales Firebird en texto plano (`sysdba`/`masterkey`), `_check2.php` con MySQL `root`/`root`, `test_jwt.php` con un JWT real.
- Contraseña por defecto `Blind001` para todos los usuarios de prueba.
- Archivos de debug en la raíz (`_debug_home.php`, `_check2.php`, `_test_login.php`, `test_jwt.php`) exponiendo PII/credenciales.
- `.env` con `APP_DEBUG=true` (mala práctica si fuera producción real).

## 3. Equipo de agentes diseñado

6 subagentes en [`.claude/agents/`](../.claude/agents/), cada uno mapeado a un problema real encontrado (no genéricos): `security-hardening`, `db-schema-migrator`, `laravel-cloud-adapter`, `frontend-build-engineer`, `integration-specialist`, `deploy-ops`. Rationale y orden de trabajo en [`docs/agent-team.md`](agent-team.md).

## 4. Auditoría completa de módulos (uso real backend + frontend)

Documentada en [`docs/cleanup-plan.md`](cleanup-plan.md):
- **54 de 112 controladores (48%) huérfanos** — 48 scaffolds 100% vacíos (nunca desarrollados), 4 con lógica real pero superados por otro controlador (`ELeadScheduleController`, `EWarehouseLocationController`, `ModulationFileControllerNEW`, `DLeadsNoteController`).
- **10-11 de 12 módulos documentados SÍ están realmente compilados y en uso** en el bundle de frontend — no era tan muerto como parecía inicialmente.
- 2 funcionalidades en producción ("Catálogo de Artículos", "Inventario Local") corren como un parche de JS crudo inyectado en `app.blade.php`, fuera del pipeline de compilación normal.
- Bug activo (P0) encontrado: `dd()` de debug olvidado en `BI/DashboardInventoryController.php`, rompía un endpoint real en uso.

## 5. Planes de QA y migración de base de datos

- [`docs/qa-test-plan.md`](qa-test-plan.md): plan en 4 fases (estático sin BD → funcional dinámico módulo por módulo → regresión post-limpieza → smoke test en Neubox pre-DNS).
- [`docs/db-migration-plan.md`](db-migration-plan.md): plan original de migración de datos — **quedó obsoleto** cuando el usuario confirmó que no existe ningún servidor de producción real ni dump de base de datos en ningún lado (ver punto 7).

## 6. Investigación de infraestructura (FTP / DNS / Neubox)

- Cuenta FTP `dev@blinds-system.com` en Neubox confirmada — servidor real: `svgt380.serverneubox.com.mx` (Pure-FTPd, TLS explícito).
- El dominio `blinds-system.com` resuelve públicamente a `74.119.239.234` (distinta a Neubox) — inicialmente se pensó que ahí vivía la producción real; **el usuario luego aclaró que no existe tal servidor, "no hay nada"**.
- Verificado subiendo un archivo de prueba: el vhost de Neubox para `blinds-system.com` **funciona correctamente** (nginx, HTTPS, redirect, headers de seguridad) — confirmado con `curl --resolve` apuntando directo a la IP de Neubox, y explicado al usuario por qué su navegador no lo veía (DNS público aún no apunta ahí).
- Intento de usar una segunda cuenta (`blindssy`) falló por contraseña incorrecta — se abandonó, se usa `dev@blinds-system.com`.

## 7. Pivote clave del proyecto

El usuario confirmó: **no hay servidor de producción real en ningún lado, ni dump de base de datos — el código es la única fuente de verdad que existe.** El proyecto pasó de "migrar datos en vivo desde un origen real" a **"reconstruir desde cero, por etapas, directamente en Neubox"**. Se documentaron las etapas en `CLAUDE.md`.

## 8. Ejecución de la Etapa 1 (limpieza y seguridad) — rama `etapa-1-limpieza-seguridad`, ya fusionada a `main`

Trabajo real ejecutado sobre el código (no solo análisis), verificado con PHP real instalado localmente (PHP 8.3, ya que el `vendor/` lo requiere):

- **Bug P0 corregido**: `dd()` eliminado de `DashboardInventoryController.php`.
- **52 controladores vacíos eliminados** (re-verificados con grep automatizado antes de borrar, no de memoria).
- **31 archivos `.bak*`/`.mojibake` eliminados** (19MB), varios dentro de `public/v/` (potencialmente servibles por HTTP).
- **Archivos de debug/credenciales eliminados**: los 4 scripts de debug de la raíz, `app/classes/env`, `app/classes/test.php` (clase inalcanzable), dumps SQL sueltos (`sp`, `sp2`, `sp3`).
- **2 bugs reales nuevos encontrados y corregidos** (solo visibles al ejecutar la app, no en lectura estática): `fpdf.php` y `PDF_Code128.php` se incluían con `require` en vez de `require_once` desde 11 controladores — cualquier request que cargara dos de ellos en el mismo proceso PHP fallaba con "constante/clase ya definida". Corregido en los 11 controladores activos.
- **`.env` de producción generado**: `APP_KEY`/`JWT_SECRET` nuevos y rotados, `APP_ENV=production`, `APP_DEBUG=false`. El `.env` local viejo (comprometido) se eliminó por completo.
- **Logs viejos eliminados** de `storage/logs/`.
- **Verificación real**: 273 archivos PHP sin errores de sintaxis (PHP 8.3), `php artisan route:list` arranca limpio con **264 rutas**, cero errores de clase faltante — confirma que nada de lo borrado rompió una ruta en uso.
- **Todo el código movido de `incoming/` (no rastreado) al repo real**, con `.gitignore` cuidadosamente verificado antes de cada `git add` para excluir `vendor/`, `node_modules/`, `.env`, sesiones/caché/logs de `storage/` — confirmado con `git check-ignore` que nada sensible se coló.
- **494 archivos** commiteados y subidos, rama fusionada a `main`.

## 8b. Herramientas instaladas en esta sesión

- `gh` (GitHub CLI)
- `php` 8.5 (versión general) + `php@8.1` y `php@8.3` (para probar compatibilidad real del código, sin cambiar el PHP global del sistema)

## Pendiente / bloqueadores abiertos

1. Confirmar tipo de servicio y accesos completos de Neubox (más allá del FTP ya usado).
2. Reconstruir el esquema de base de datos desde cero (no hay dump — inferir de `app/Models/` y fragmentos SQL sueltos).
3. Decisiones de producto pendientes: módulo "Configuración" (24+ catálogos nunca implementados), formalizar los 2 hacks runtime (Artículos/Inventario Local) como componentes Vue reales.
4. Limpieza cosmética pendiente (bajo impacto): comentarios "// OLD" en `FPDFMR*.php`.
5. QA funcional dinámico real (Fase 2 en adelante) — requiere ambiente con base de datos, aún no se puede ejecutar.
