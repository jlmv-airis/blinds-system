# Runbook de validación real del MVP (local, Mac)

Comandos ejecutables tal cual, para correr tú mismo. Todo lo de este documento se ejecutó de verdad el 2026-08-25 contra `blindsystem_mvp` en MySQL local — no son ejemplos hipotéticos, son los comandos reales con los resultados reales obtenidos (ver debajo de cada bloque).

## 0. Pre-requisitos: confirmar que el ambiente está arriba

```bash
brew services list | grep mysql
ps aux | grep "artisan serve" | grep -v grep
```
Si no hay servidor corriendo:
```bash
cd ~/Documents/GitHub/blinds-system
php artisan serve --host=127.0.0.1 --port=8000
```
(usar el binario correcto si el PHP global no es 8.3+: `/opt/homebrew/opt/php@8.3/bin/php`)

## 1. Migraciones aplicadas — cómo confirmarlo

```bash
php artisan migrate:status
```
**Resultado real obtenido**: las 7 migraciones del MVP con `Ran? = Yes` (4 base de Laravel + `add_role_to_users_table` + `create_clients_table` + `create_products_table`). Si alguna dice `No`, correr:
```bash
php artisan migrate --path=database/migrations/<archivo_faltante>.php
```

## 2. Base de datos sincronizada — cómo confirmarlo

No basta con que `migrate:status` diga "Yes" — hay que confirmar que las columnas reales en MySQL coinciden con lo que el modelo espera:

```bash
mysql -u root blindsystem_mvp -e "SHOW TABLES;"
mysql -u root blindsystem_mvp -e "DESCRIBE users;"
mysql -u root blindsystem_mvp -e "DESCRIBE clients;"
mysql -u root blindsystem_mvp -e "DESCRIBE products;"
```
**Resultado real obtenido**: 7 tablas (`clients`, `failed_jobs`, `migrations`, `password_resets`, `personal_access_tokens`, `products`, `users`). `users` tiene la columna `role varchar(255) default 'user'`. `clients` y `products` con exactamente los campos declarados en sus migraciones.

## 3. Login real — `/api/login`

```bash
BASE="http://127.0.0.1:8000/api"

# caso correcto — debe dar 200 y un token
curl -sS -X POST "$BASE/login" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@blinds-system.com","password":"ChangeMe123!"}' -w "\nHTTP: %{http_code}\n"

# password incorrecto — debe dar 401
curl -sS -X POST "$BASE/login" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@blinds-system.com","password":"incorrecta"}' -w "\nHTTP: %{http_code}\n"

# sin token, ruta protegida — debe dar 401
curl -sS "$BASE/clients" -H "Accept: application/json" -w "\nHTTP: %{http_code}\n"
```
**Resultado real obtenido**: 200 con token Sanctum válido (`N|...`) en el caso correcto; 401 `{"success":false,"message":"Credenciales incorrectas"}` con password malo o email inexistente; 401 `{"message":"Unauthenticated."}` sin token.

Guarda el token de la respuesta correcta en una variable para los siguientes pasos:
```bash
TOKEN="pega_aqui_el_token_completo_incluyendo_el_1|..."
AUTH="Authorization: Bearer $TOKEN"
```

## 4. CRUD de clientes — `/api/clients`

```bash
# listar
curl -sS "$BASE/clients" -H "$AUTH" -H "Accept: application/json"

# crear (válido)
curl -sS -X POST "$BASE/clients" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Cliente Prueba","email":"c@test.com"}' -w "\nHTTP: %{http_code}\n"

# crear sin nombre — debe dar 422
curl -sS -X POST "$BASE/clients" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"sinnombre@test.com"}' -w "\nHTTP: %{http_code}\n"

# editar (usa el id real que te devolvió el create)
curl -sS -X PUT "$BASE/clients/<ID>" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"phone":"5599998888"}' -w "\nHTTP: %{http_code}\n"

# eliminar (soft-delete)
curl -sS -X DELETE "$BASE/clients/<ID>" -H "$AUTH" -H "Accept: application/json" -w "\nHTTP: %{http_code}\n"

# confirmar en MySQL directo que fue soft-delete, no borrado físico
mysql -u root blindsystem_mvp -e "SELECT id,name,is_active FROM clients WHERE id=<ID>;"
```
**Resultado real obtenido**: create → 201; crear sin nombre → 422 `{"errors":{"name":["El campo nombre es obligatorio."]}}`; update → 200 con los datos actualizados; delete → 200, y confirmado en MySQL que el registro sigue existiendo con `is_active=0` (no `DELETE` físico).

## 5. CRUD de productos — `/api/products`

```bash
curl -sS "$BASE/products" -H "$AUTH" -H "Accept: application/json"

curl -sS -X POST "$BASE/products" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"sku":"PRUEBA-001","name":"Producto Prueba","price":100}' -w "\nHTTP: %{http_code}\n"

# SKU duplicado — debe dar 422
curl -sS -X POST "$BASE/products" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"sku":"PRUEBA-001","name":"Otro","price":10}' -w "\nHTTP: %{http_code}\n"

# precio negativo — debe dar 422
curl -sS -X POST "$BASE/products" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"sku":"PRUEBA-002","name":"Malo","price":-1}' -w "\nHTTP: %{http_code}\n"

curl -sS -X PUT "$BASE/products/<ID>" -H "$AUTH" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"stock":25}' -w "\nHTTP: %{http_code}\n"

curl -sS -X DELETE "$BASE/products/<ID>" -H "$AUTH" -H "Accept: application/json" -w "\nHTTP: %{http_code}\n"
```
**Resultado real obtenido**: create → 201; SKU duplicado → 422 `{"errors":{"sku":["El valor de SKU ya ha sido registrado."]}}`; precio negativo → 422 `{"errors":{"price":["El campo precio debe ser al menos 0."]}}`; update → 200; delete → 200, confirmado `is_active=0` en MySQL directo.

## 6. Permisos por rol

```bash
# login como usuario normal (no admin)
curl -sS -X POST "$BASE/login" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"user@blinds-system.com","password":"User123!"}'

# con ese token, intentar eliminar — debe dar 403
curl -sS -X DELETE "$BASE/products/<ID>" -H "Authorization: Bearer <token_de_user>" -H "Accept: application/json" -w "\nHTTP: %{http_code}\n"
```
**Resultado real obtenido**: 403 `{"success":false,"message":"No tienes permisos para esta acción"}`.

## Resumen de la corrida real del 2026-08-25

19 comandos ejecutados, 19 con el código HTTP y el cuerpo esperado. Estado final verificado directo en MySQL (no en la respuesta de la API):

| Tabla | Filas activas (`is_active=1`) | Filas soft-eliminadas |
|---|---|---|
| `clients` | 1 | 2 |
| `products` | 2 | 2 |
| `users` | 2 (1 admin, 1 user) | — |
| `personal_access_tokens` | 6 emitidos en total | — |

Nada de esto se simuló — cada bloque de este documento tiene su ejecución real registrada en la sesión que lo generó.

## Corrida adicional — 2026-08-25 20:04–20:06

Segunda corrida completa (12 casos: auth, CRUD clientes con validación de email, CRUD productos, 404 en ID inexistente, 403 por rol) — 12/12 con el resultado esperado. Cada delete/update inválido se verificó cruzando contra MySQL directo, no solo la respuesta HTTP.

**Hallazgo nuevo, sin corregir**: `GET` a cualquier ruta protegida por `auth:sanctum` **sin** header `Accept: application/json` devuelve `500` en vez de `401`. Causa: `app/Http/Middleware/Authenticate.php:18` (clase base de Laravel, sin personalizar) llama a `route('login')` en `redirectTo()`, pero no existe ninguna ruta nombrada `login` en el proyecto (ni en `web.php` ni en `api.php`) → `RouteNotFoundException` sin capturar. No afecta a clientes que sí mandan `Accept: application/json` (incluido `public/mvp/js/api.js`).

```bash
# reproducir
curl -sS -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/api/me
# -> 500

curl -sS -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/api/me -H "Accept: application/json"
# -> 401 (correcto)
```
