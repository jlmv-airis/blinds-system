# Auditoría de seguridad — sistema legacy (no tocado, solo documentado)

Fecha: 2026-08-25. Alcance: código legacy completo (fuera de scope del MVP actual, que vive en `app/Http/Controllers/Api/`). Se documenta aquí para cuando se retome el legacy — **no se corrigió nada de esto**, por instrucción explícita de enfocarnos en el MVP.

## 🔴 Crítico — SQL Injection real, confirmado

1. **Concatenación directa en `CALL sp_modulation(...)`**: `EMaterialRequestController.php:375,380,394,869`, `EOrderController.php:3028,3052,3216,3240`. `$request->request_id`/`order_id` se concatenan en el string SQL antes de `prepare()` — el placeholder no protege nada. Exploit confirmado conceptualmente: `request_id = "(SELECT SLEEP(5))"` produce SQLi ciego basado en tiempo.
2. **`DB::raw($request->...)` sin sanitizar** en joins/selects: `EQuotationController.php:686` (bypassa el filtro de "solo mis cotizaciones" con `user_id = "0 OR 1=1"`), `EScheduleController.php:301`, `CUserController.php:487`, `ESectionController.php:108`. Patrón recurrente adicional sin trazar completo en `EMaterialRequestController`, `EGuarantyController`, `EOrderController`, `CErpUserController`, `ENotificationController` — necesita un pase dedicado.

## 🔴 Crítico — IDOR / escalación de privilegios

Ningún endpoint de actualización (cotizaciones, órdenes, clientes, permisos de usuario) verifica que el registro pertenezca al usuario/empresa que hace la petición — todos confían en el ID que manda el cliente:
- `EQuotationController.php:299-373` — cualquier agente autenticado puede editar/cancelar la cotización de cualquier otro.
- `CUserController.php:284` — cualquier usuario puede cambiar el descuento o el agente asignado de cualquier cliente.
- `CErpUserController.php:196,222` — **cualquier empleado autenticado puede autoasignarse `is_leader=1` o acceso a cualquier módulo** — escalación de privilegios directa.

Causa raíz sistémica: casi todos los controladores leen `$request->user_id` como "quién soy" en vez de derivarlo del usuario autenticado (`auth()->id()` / JWT).

## 🟡 Medio — uploads sin validar

`CLocalInventoryController.php:97`, `ImportFileController.php:24`, `CInventoryProductController.php:245` — aceptan cualquier archivo sin validar mimetype/tamaño antes de parsearlo.

## Confirmado sin hallazgos

- XSS: cero `{!! !!}` en Blade, cero `innerHTML`/`v-html` en el JS embebido.
- Mass assignment: no se encontró `::create($request->all())`.
- CORS: configuración actual es segura (`origins: *` pero `credentials: false`).
