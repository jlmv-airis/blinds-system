# Plan de QA — Blindsystem V5.4

Objetivo: probar exhaustivamente cada función del sistema (como QA tester, no solo lectura de código) antes y después de la limpieza y del despliegue a Neubox. Se apoya en la auditoría ya hecha (`docs/scan-findings.md`, `docs/cleanup-plan.md`) para saber exactamente qué rutas y módulos existen de verdad.

## Bloqueador honesto, antes de prometer nada

**No se puede ejecutar QA funcional real todavía.** Probar "todas las funciones" requiere una app corriendo contra una base de datos con datos reales o al menos realistas (119 tablas, catálogos de artículos, usuarios, órdenes). Hoy no tenemos:
- El dump real (`LansonAllDB.sql`) — sigue siendo el bloqueador principal.
- Acceso confirmado al servidor de producción real (`74.119.239.234`) para validar contra el sistema vivo.
- Un ambiente PHP+MySQL corriendo localmente o en Neubox con datos cargados.

Por eso el plan se divide en fases: lo que se puede hacer **hoy, sin DB**, y lo que requiere que primero resolvamos el acceso a la BD real.

## Fase 1 — QA estático (se puede hacer ya, sin base de datos)

No prueba "funciona correctamente para el negocio", pero sí atrapa errores que romperían el sistema antes de llegar a producción:

1. **Sintaxis PHP**: `php -l` sobre los ~337 archivos de `app/` — detecta errores de sintaxis sin necesitar DB.
2. **Integridad de dependencias**: `composer install` y `npm install` limpios, sin errores — confirma que `composer.lock`/`package-lock.json` son consistentes con lo declarado.
3. **Boot de la aplicación sin DB**: intentar `php artisan route:list` y `php artisan config:cache` — algunos Service Providers pueden fallar si intentan conectar a DB en el boot; si falla, documentamos exactamente dónde para no sorprendernos después.
4. **Regresión de la limpieza (Fase 1 del plan de pulido)**: después de borrar los 48 controladores huérfanos y los `.bak`, repetir `route:list` y confirmar que el conteo de rutas activas no cambió — si cambia, algo que sí se usaba se borró por error.
5. **Checklist de endpoints por módulo** (inventario para las siguientes fases, no prueba en sí): tabla de todas las rutas reales por módulo, ya generada en la auditoría — sirve de checklist para clic-a-clic cuando haya ambiente.

## Fase 2 — QA funcional dinámico (requiere DB real o de prueba + app corriendo)

Por módulo, con foco en lo que la auditoría ya marcó como sospechoso o fuera de lo común — no es una lista genérica de CRUD:

| Módulo | Qué probar específicamente | Por qué (hallazgo previo) |
|---|---|---|
| Autenticación | Login Firebase (flujo real de producción) **y** login directo `/auth/login` (flujo de desarrollo) — confirmar cuál es el vigente hoy en producción | Hay dos flujos coexistiendo; `setup/patches/Login.js` bypassa Firebase |
| Usuarios / permisos | Verificar que el menú y accesos cambian correctamente según `d_erp_access_users` (módulo/submódulo por usuario) | Es la base de toda la seguridad de UI, nunca se ha probado formalmente |
| Multi-marca (LS/RT/LB) | Crear/consultar registros como usuario de cada marca, confirmar que NO se ve ni se mezcla data de otra marca | Es un ERP multi-tenant sobre una sola BD — el riesgo de fuga entre marcas es real |
| Cotizaciones → Catálogo de Artículos | Probar específicamente `/quotations/articles` (crear/editar/eliminar artículo) | Es el hack runtime inyectado en `app.blade.php`, no está en el bundle compilado — el más frágil del sistema ante cualquier cambio |
| Inventario → Inventario Local | Probar `/inventory/local`, incluyendo import/export CSV | Mismo hack runtime que Artículos |
| Órdenes | Ciclo completo: cotización → orden → modulación → producción → empaque → envío | Es el módulo más grande (~5300 líneas en un solo controlador) — el de mayor superficie de bugs posible |
| Producción | Confirmar acceso a `/sections` **sin login** (hoy no tiene `jwt.auth`) — validar si es intencional o hay que cerrarlo antes de ir a producción | Hallazgo de seguridad ya documentado |
| Reportes BI | Los 9 dashboards, especialmente `downloadInventoryLotsExcel` después del fix del `dd()` | Bug P0 activo conocido |
| Garantías | Flujo de creación de garantía + generación de PDF | PDFs siempre traen branding "Lanson Shades" sin importar la marca real — confirmar si es aceptable o hay que corregirlo |
| Compras | Flujo completo de proveedor → compra → recepción (`scans`) | Vive anidado dentro de `/warehouses`, sin módulo propio — confirmar que funciona igual aunque esté mal organizado en código |
| Integración ASPEL | Probar los dashboards de BI **con y sin conectividad** a `aspelroller3.ddns.net:81` | Debe fallar de forma segura (hay parches para esto) — confirmar que el fallback real funciona, no solo el de desarrollo |
| Exportaciones Excel | Probar al menos una exportación por grupo funcional (son 37 clases `Export`) | Verificar que ninguna se rompió con la limpieza de Fase 1 del plan de pulido |
| `/articles/prices*` sin auth | Confirmar impacto real de negocio si se cierra este endpoint (quién lo usa hoy y cómo) antes de bloquearlo | Es escritura de precios sin autenticación — hay que saber si algo depende de que esté abierto antes de cerrarlo a ciegas |

## Fase 3 — QA de regresión post-limpieza

Después de que yo aplique la Fase 1 del plan de pulido (borrar controladores huérfanos, `.bak`, arreglar el `dd()`), repetir:
- `route:list` (conteo de rutas no debe cambiar)
- Smoke test de cada módulo de la tabla de arriba (aunque sea con datos mínimos/sintéticos)
- Confirmar que los dos hacks runtime (Artículos, Inventario Local) siguen funcionando — son los más frágiles ante cualquier cambio, incluso cambios que no los tocan directamente

## Fase 4 — QA en Neubox, antes del corte de DNS

- Repetir el smoke test completo contra `dev@blinds-system.com` en Neubox, usando el truco de `/etc/hosts` o `--resolve` (ya validado que el vhost funciona).
- Confirmar `.env` de producción real: `APP_ENV=production`, `APP_DEBUG=false`, credenciales nuevas y rotadas (no las del ambiente local).
- Confirmar certificado SSL (ya vimos que Neubox está tramitando uno) válido antes de cualquier corte de DNS.
- Solo después de esto se considera el corte de DNS — es un cambio con impacto real en usuarios en producción, requiere tu confirmación explícita en el momento, no se hace como parte de "limpieza".

## Qué necesito de ti para desbloquear la Fase 2 en adelante

1. Acceso real a `74.119.239.234` (o donde sea que viva la producción real) — para el dump y para comparar comportamiento real.
2. Confirmar si hay un ambiente de staging/pruebas ya existente, o si hay que crear uno (local con Docker/Laragon, o directamente en Neubox con datos de prueba).
3. Credenciales de Firebase del proyecto real (para poder probar el login de producción, no solo el bypass local).
