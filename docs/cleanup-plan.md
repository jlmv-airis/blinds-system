# Plan de pulido — Blindsystem V5.4

Fecha: 2026-08-25
Objetivo del usuario: identificar qué sirve y qué no, congelar/eliminar lo que no se usa, **sin tocar la base de datos** (esquema y datos intactos — todo lo de este plan es a nivel de código: controladores, rutas, config, bundle de frontend).

Metodología: 3 auditorías independientes — (1) mapeo backend rutas↔controladores↔módulos, (2) qué está realmente conectado en el frontend vivo (incluyendo el bundle compilado, ya que el código fuente Vue real no existe en el repo), (3) código muerto/artefactos legacy. Todo con evidencia archivo:línea, nada especulativo.

## Hallazgo central

De los 112 controladores del backend, **54 (48%) son huérfanos** — no los alcanza ninguna ruta. De esos, 48 son *scaffolds* vacíos (`artisan make:controller`, cada método es literalmente `//`) que nunca se desarrollaron. En el frontend, en cambio, **10 de los 12 módulos documentados sí están realmente compilados y en uso** en el bundle actual — la sospecha inicial de "casi todo es código muerto" no se confirmó a ese nivel; el problema real está concentrado en controladores de catálogos nunca construidos y en dos funcionalidades ("Catálogo de Artículos" e "Inventario Local") que sí están vivas en producción pero corren como un parche inyectado en tiempo de ejecución, fuera del pipeline normal de compilación.

## Matriz de módulos (backend + frontend + veredicto)

|                                |                                     |                              |                                                        |
|--------------------------------|-------------------------------------|------------------------------|--------------------------------------------------------|
| **Módulo**                     | **Backend**                          | **Frontend**                  | **Veredicto**                                            |
| Dashboard/Home                 | Vivo (bajo `/bi`, sin prefijo propio) | Vivo (`/home`, `/business-intelligence/my-dashboard`) | Mantener. Bug activo (ver P0 abajo). |
| Usuarios                       | Vivo                                  | Vivo (`/settings/users`)       | Mantener. |
| Clientes                       | Vivo (incluye Leads)                  | Vivo (`/clients/*`)            | Mantener. `ELeadScheduleController` huérfano parece superado por `EScheduleController` — confirmar y retirar. |
| Cotizaciones                   | Vivo                                  | **Parcial** — flujo principal vivo; submódulo "Catálogo de Artículos" no existe en el bundle compilado, es un hack runtime en `app.blade.php` | Mantener el módulo. **El hack de Artículos necesita convertirse en componente Vue real** (está en producción, no se puede simplemente borrar). |
| Órdenes                        | Vivo (el más grande, ~5300 líneas en `EOrderController`) | Vivo (`/orders/*`) | Mantener. Varios huérfanos: `DOrderController`, `CMaterialRequestController`, `DMaterialRequestController`, `DAddMaterialRequestController` (stubs), y `ModulationFileControllerNEW.php` (fork completo de 611 líneas, sin rutear, más viejo que la versión activa) — eliminar tras confirmar. |
| Producción                     | Vivo — **pero `sections` corre sin `jwt.auth`** | Vivo (`/production/*`) | Mantener, cerrar el hueco de auth (coordinar con `security-hardening`). |
| Inventario/Almacén             | Vivo                                  | **Parcial** — flujo oficial vivo; "Inventario Local" (con import/export CSV) es el mismo patrón de hack runtime que Artículos | Mantener el módulo. Mismo tratamiento que Cotizaciones: formalizar como componente real. Además: ~15 controladores de catálogo de producto (`CChainController`, `CMechanismController`, `CTubeController`, `CTypeMotorController`, etc.) son stubs vacíos nunca implementados. |
| Compras                        | Vivo, pero **anidado dentro de `/warehouses`**, sin prefijo propio | Vivo (`/warehouse/purchases`) | Mantener funcionalmente. Reorganizar bajo su propio prefijo de ruta es limpieza estructural de bajo riesgo, no urgente. |
| Garantías                      | Vivo (`EGuarantyController`)          | Vivo (`/guarantees/*`)         | Mantener. Catálogos relacionados (`DGuarantyController`, tipos/errores de garantía) son stubs — decidir si se construyen o se retiran. |
| Envíos                         | Vivo, pero sin controlador propio (vive dentro de `EOrderController`) | Vivo (`/shipments/*`) | Mantener — es una decisión de organización de código, no un problema funcional. |
| Reportes BI                    | Vivo (9 dashboards)                   | Vivo                           | Mantener. **Bug activo P0** (ver abajo). |
| Configuración                  | **Mayormente stubs** — ~24 controladores de catálogo vacíos | **No se pudo confirmar una ruta de nivel superior dedicada** en el bundle | Requiere decisión de producto: ¿este módulo se sigue construyendo o se cancela formalmente? Ver sección de decisiones. |

## 🔴 P0 — bug activo, no es limpieza, es un error real

- `app/Http/Controllers/BI/DashboardInventoryController.php:24` tiene un `dd($rowData->items)` (var-dump-and-die de Laravel) **dentro de un método ruteado y en uso** (`downloadInventoryLotsExcel`). Cada vez que un usuario llama a esa función, la ejecución se corta y muestra un dump de debug en vez de generar el archivo. Corregir antes que cualquier otra cosa de este plan — es una funcionalidad rota hoy, no deuda técnica.

## Fase 1 — Bajo riesgo, alto impacto (limpieza mecánica)

Seguro de ejecutar porque ninguno de estos archivos es alcanzable por ninguna ruta ni referencia — confirmado por grep exhaustivo, no por inferencia:

- **48 controladores scaffold vacíos** (0% lógica, cero rutas apuntándoles) — eliminar. Lista completa en el hallazgo de la auditoría backend.
- **30 archivos `.bak*` fuera de control de versiones (~12.5MB)**, varios dentro de `public/v/0.3.16/js/` — es decir, **potencialmente servibles por HTTP** si alguien adivina la URL. Riesgo de seguridad menor + confusión sobre cuál es la versión vigente. Eliminar.
- `app/classes/test.php` — declara una clase con namespace `App\Http\Controllers` pero vive en `app/classes/`; por el autoload PSR-4 del `composer.json`, **nunca puede cargarse** — código inalcanzable en runtime. Eliminar.
- `app/classes/sp`, `sp2`, `sp3` — dumps de texto plano de un stored procedure, no son código PHP, no se usan. Mover fuera del árbol de la app o eliminar.
- Bloques de comentarios "// OLD" en la familia `FPDFMR*.php` (código de PDF viejo comentado, ~15 líneas cada uno en 4 archivos). Eliminar.
- Import muerto `use ShouldQueue` en `app/Mail/SendQuotationMailable.php` (nunca se implementa la interfaz). Eliminar el import.

## Fase 2 — Riesgo medio, requiere confirmación antes de borrar

Estos archivos SÍ tienen lógica real (no son stubs), pero parecen superados por una versión activa. No borrar sin verificar que efectivamente no se necesitan como referencia:

- `ModulationFileControllerNEW.php` (Órdenes) — fork sin rutear, más viejo que el activo.
- `ELeadScheduleController.php` (Clientes) — probablemente reemplazado por `EScheduleController`.
- `EWarehouseLocationController.php` (Almacén) — probablemente reemplazado por `DWarehouseLocationController`.

**Feature de tiempo real nunca completada** — decidir si se retoma o se retira formalmente:
- `socket-server.js` (Node, puerto 3000, sin auth, CORS abierto, sin lógica de negocio conectada).
- `DSocketConnectionController` (stub, sin rutear).
- `config/broadcasting.php` configurado con Pusher/Ably pero `BROADCAST_DRIVER=log` — cero eventos `ShouldBroadcast` en todo el código.
- Si no hay plan de retomar esto pronto: eliminar el proceso Node y los stubs relacionados. Si sí hay plan: al menos cerrar el CORS abierto del socket server ya (coordinar con `security-hardening`).

**Feature "Carrito organizador" abandonada** — 3 controladores stub (`EOrganizerCartController`, `COrganizerCartController`, `DOrganizerCartController`), cero rastro en frontend ni documentación viva. Candidato a eliminar salvo que el usuario confirme que sigue en el roadmap.

## Fase 3 — Decisiones de producto (no solo de código)

1. **Módulo Configuración**: ~24 controladores de catálogo de producto (colores, modelos, mecanismos, tubos, motores, líneas de producción, etc.) existen como scaffolds vacíos sin ninguna ruta ni evidencia de frontend. ¿Este módulo se sigue construyendo (y entonces son trabajo pendiente, no basura) o se cancela formalmente (y entonces se eliminan)? Sin esta decisión no se puede tratar esta parte del código.
2. **"Catálogo de Artículos" e "Inventario Local"**: están en producción hoy, pero implementados como un parche de ~480 líneas de JS crudo inyectado en `app.blade.php` en vez de componentes Vue reales compilados. Recomendación: convertirlos en componentes `.vue` normales, versionados junto al resto del frontend — son funcionalidad real que la empresa usa, no se pueden simplemente apagar.
3. **`public/v/0.3.16/js/`**: el bundle "oficial" tiene ediciones manuales directas (los `.bak*` con fechas de agosto 2026) sin que exista el código fuente Vue correspondiente en el repo. Esto es más grave que "limpieza" — significa que hoy no hay forma de reconstruir el frontend desde cero. Este punto ya está cubierto por el agente `frontend-build-engineer`, pero es prerequisito real para cualquier plan de pulido serio del frontend.

## Fase 4 — Config y tests (baja urgencia, documentar y decidir)

- `config/broadcasting.php`, `config/queue.php`, `config/filesystems.php`, `config/services.php` están prácticamente en sus valores por defecto de Laravel, sin conexión real con el código (todo corre en `sync`/`local`/`log`). Se puede simplificar a lo mínimo real sin riesgo, una vez resuelta la decisión de tiempo real (Fase 2).
- Cobertura de tests real: **0%** (solo el scaffold de ejemplo de Laravel). No es código muerto, es ausencia total — se documenta aquí como decisión pendiente de inversión, no como algo a "limpiar".

## Ejecución — actualización 2026-08-25 (rama `etapa-1-limpieza-seguridad`)

Aplicado y verificado con PHP real (no solo lectura de código):

- **Bug P0 corregido**: `dd()` eliminado de `DashboardInventoryController.php:24`.
- **52 controladores vacíos eliminados** (confirmados 100% stub por grep automatizado, no por la lista de memoria del análisis anterior — se re-verificó cada uno).
- **31 archivos `.bak*`/`.mojibake` eliminados** (19MB), incluyendo los que estaban dentro de `public/v/` (potencialmente servibles por HTTP).
- **Archivos de debug/credenciales eliminados**: `_debug_home.php`, `_check2.php`, `_test_login.php`, `test_jwt.php`, `app/classes/env` (Firebird), más `app/classes/test.php` (clase inalcanzable) y los dumps SQL sueltos `sp`/`sp2`/`sp3`.
- **Import muerto** (`ShouldQueue` en `SendQuotationMailable.php`) y **3 imports rotos en `routes/web.php`** (`DWarehouseLevelController` — el archivo no existe — y dos modelos sin uso) eliminados.

**2 bugs reales nuevos encontrados y corregidos, no estaban en el análisis original** (solo aparecen al ejecutar la app de verdad, no con lectura estática de código):
- `app/fpdf/fpdf.php` y `app/fpdf/PDF_Code128.php` se incluían con `require` (no `require_once`) desde 11 controladores distintos — cualquier request real que cargue más de uno de esos controladores en el mismo proceso PHP truena con "constant/class already defined". Corregido en los 11 controladores activos (se dejó `ModulationFileControllerNEW.php` intacto, sigue pendiente de confirmación).
- **El `vendor/` que viene en el zip requiere PHP >= 8.3**, aunque `composer.json` declara `^7.3|^8.0` — inconsistencia entre lo declarado y lo realmente instalado. Importante para `deploy-ops`: Neubox necesita PHP 8.3+ disponible, no una versión más vieja como sugiere la documentación.

**Verificación de regresión**: con PHP 8.3 real, `php artisan route:list` corre limpio — **264 rutas**, cero errores de clase faltante. Confirma que ninguna de las eliminaciones rompió una ruta real (si algo borrado estuviera en uso, `route:list` habría fallado al resolver esa ruta).

**`.env` de producción generado**: `APP_KEY` y `JWT_SECRET` nuevos (rotados, no reutilizan nada del local), `APP_ENV=production`, `APP_DEBUG=false`. `DB_*` y credenciales de AWS/Pusher quedan vacías con `# TODO` — se llenan cuando exista la base de datos real en Neubox (ver `docs/db-migration-plan.md`) y si de verdad se decide usar esos servicios (el escaneo no encontró uso real de `Storage::disk()` ni `Broadcast::` en el código). El `.env` local viejo (con las credenciales ya comprometidas) se eliminó del todo, no solo se reemplazó.

**Logs viejos eliminados** de `storage/logs/` (2.9MB, incluían trazas de la sesión de pruebas de hoy) — no aportan nada al ambiente nuevo.

**Verificación final**: 273 archivos PHP (`app/`, `routes/`, `config/`) sin errores de sintaxis con PHP 8.3 real.

Pendiente en esta misma rama (bajo impacto, no bloqueante): quitar comentarios "// OLD" en `FPDFMR*.php` (cosmético, el texto exacto no calzó por espacios/tabs, se puede retomar después).

## Confirmado: nada de esto toca la base de datos

Todo el plan opera sobre controladores, rutas, archivos de config, y artefactos del bundle de frontend. No se propone ningún cambio de esquema, migración destructiva, ni borrado de datos — consistente con el requisito del usuario de mantener las bases de datos intactas.

## Siguiente paso sugerido

Empezar por el bug P0 (`dd()` en `DashboardInventoryController.php:24`) y la Fase 1 (limpieza mecánica de bajo riesgo) — ambas no requieren ninguna decisión de producto y se pueden ejecutar ya. Las Fases 2 y 3 necesitan que el usuario confirme decisiones puntuales antes de tocar código.
