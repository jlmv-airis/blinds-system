# MVP funcional — login, clientes, productos, permisos

Rama: `fix/production-ready`. Objetivo del usuario: dejar de intentar reparar los 119 módulos del legacy y construir un núcleo pequeño, real y probado, que corra local y luego en Neubox (cPanel).

## Decisión de diseño

No se reutilizó el sistema de auth legacy (`CErpUserController`/`CErpUser`, ligado a `c_erp_users`/`c_erp_info_users`/`c_companies`/`c_departments`, ninguna con esquema real disponible). En su lugar:

- **Auth**: tabla `users` estándar de Laravel (ya tenía migración) + **Laravel Sanctum** (ya estaba instalado, `laravel/sanctum` en `composer.json`, solo faltaba usarlo) para tokens de API. Se evita así el bug crítico encontrado en el login legacy (`CErpUserController.php:319`: `json_decode($user->accessModules, true)` sobre una **relación Eloquent**, no un string — tronaba con `TypeError` en cada intento de login). El MVP no tiene ese patrón en absoluto.
- **Permisos**: columna `role` en `users` (`admin` / `user`), sin tocar el sistema de módulos/submódulos legacy.
- **Clientes y Productos**: tablas nuevas y limpias (`clients`, `products`), no las tablas legacy `c_users`/`c_articles` (esas no tienen esquema real en ningún lado — ver `docs/scan-findings.md`).
- **Validación real**: `FormRequest` en cada endpoint de escritura — el legacy no tenía ni un solo caso de esto en toda la app (confirmado por auditoría).
- **Respuestas consistentes**: siempre `{success, data|message|errors}` con el código HTTP correcto (200/201/401/403/422/500) — el legacy mezclaba 200/400/403 para errores de forma inconsistente.

Todo esto vive en `routes/api.php` (antes sin usar) — el legacy en `routes/web.php` no se tocó.

## Archivos nuevos

- Migraciones: `database/migrations/2026_08_25_100000_add_role_to_users_table.php`, `..._100100_create_clients_table.php`, `..._100200_create_products_table.php`
- Modelos: `app/Models/Client.php`, `app/Models/Product.php` (+ `role` agregado a `app/Models/User.php`)
- `app/Http/Requests/{Store,Update}{Client,Product}Request.php`
- `app/Http/Controllers/Api/{Auth,Client,Product}Controller.php`
- `app/Http/Middleware/EnsureUserIsAdmin.php` (alias `admin` en `app/Http/Kernel.php`)
- `database/seeders/MvpSeeder.php` (usuario admin de prueba)
- `resources/lang/es/validation.php` — **bug preexistente corregido de paso**: `config/app.php` tenía `locale=es` pero no existía `resources/lang/es/`, así que todo mensaje de validación (legacy y nuevo) salía como `validation.required` en vez de texto legible. Se creó el archivo y se cambió `fallback_locale` a `en` para que cualquier clave faltante caiga a inglés en vez de mostrarse cruda.

## Cómo correrlo en local (Mac) — pasos exactos, ya verificados

```bash
# 1. PHP 8.3 (el vendor/ del proyecto requiere 8.3+, aunque composer.json declare 7.3|8.0)
brew install php@8.3

# 2. MySQL
brew install mysql
brew services start mysql
mysql -u root -e "CREATE DATABASE blindsystem_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. .env — usar estos valores para LOCAL (el .env real de producción está documentado
#    en docs/db-migration-plan.md, no lo reutilices aquí):
#    APP_ENV=local
#    APP_DEBUG=true
#    APP_URL=http://127.0.0.1:8000
#    DB_HOST=127.0.0.1
#    DB_DATABASE=blindsystem_mvp
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Migrar solo lo del MVP (no las 119 tablas legacy que no existen)
php artisan migrate --path=database/migrations/2014_10_12_000000_create_users_table.php \
  --path=database/migrations/2014_10_12_100000_create_password_resets_table.php \
  --path=database/migrations/2019_08_19_000000_create_failed_jobs_table.php \
  --path=database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php \
  --path=database/migrations/2026_08_25_100000_add_role_to_users_table.php \
  --path=database/migrations/2026_08_25_100100_create_clients_table.php \
  --path=database/migrations/2026_08_25_100200_create_products_table.php

# 5. Seed (crea admin@blinds-system.com / ChangeMe123!)
php artisan db:seed --class=MvpSeeder

# 6. Correr
php artisan serve --host=127.0.0.1 --port=8000
```

**Puerto**: `8000`. **Rutas principales para probar**: `POST /api/login`, `GET|POST /api/clients`, `PUT|DELETE /api/clients/{id}`, `GET|POST /api/products`, `PUT|DELETE /api/products/{id}`, `GET /api/me`, `POST /api/logout`.

## Pruebas reales ejecutadas (no simuladas — corridas contra el servidor local de verdad)

| # | Prueba | Resultado |
|---|---|---|
| 1 | Login con credenciales correctas | ✅ 200, token real emitido |
| 2 | Login con password incorrecto | ✅ 401 |
| 3 | Request sin token / con token inválido | ✅ 401 en ambos casos |
| 4 | Crear cliente válido | ✅ 201 |
| 5 | Crear cliente sin `name` | ✅ 422, error de validación claro |
| 6 | Editar cliente | ✅ 200, cambios aplicados |
| 7 | Eliminar cliente (soft-delete) | ✅ 200, desaparece del listado, sigue accesible por ID directo |
| 8 | Crear producto válido | ✅ 201 |
| 9 | Crear producto con SKU duplicado | ✅ 422 |
| 10 | Crear producto con precio negativo | ✅ 422 |
| 11 | Editar producto | ✅ 200 |
| 12 | Eliminar producto (soft-delete) | ✅ 200 |
| 13 | Usuario `role=user` crea producto | ✅ 201 (permitido) |
| 14 | Usuario `role=user` intenta eliminar producto | ✅ 403 (bloqueado correctamente) |

## Cómo pasar esto a Neubox (cPanel) después

1. Restaurar en `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` real, y credenciales de una base de datos MySQL creada en el panel de Neubox (no reutilizar las locales).
2. Correr las mismas migraciones del MVP contra esa base (los mismos 7 `--path` de arriba).
3. Cambiar la contraseña del seed de `MvpSeeder` antes de correrlo en producción, o crear el admin manualmente.
4. Subir el código vía FTPS a `dev@blinds-system.com` (ya validado que el vhost de Neubox funciona — ver `CLAUDE.md`).

## Explícitamente fuera de este MVP (por instrucción del usuario)

- No se reconstruyó el esquema de las 119 tablas legacy.
- No se tocó la integración con ASPEL.
- El resto de los módulos del ERP legacy (órdenes, cotizaciones, garantías, etc.) sigue como está, documentado pero no reparado — ver `docs/security-audit-legacy.md` para los hallazgos críticos de seguridad encontrados ahí (SQLi, IDOR) que quedan pendientes para cuando se retome esa parte.
