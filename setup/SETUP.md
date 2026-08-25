# SETUP.md — Lanson Shades ERP · Guía de Instalación Local (Windows)

---

## 🎯 Objetivo

Levantar este proyecto Laravel 8 + Vue 2 localmente en **Windows** usando el servidor PHP built-in (sin Laragon, sin Apache, sin XAMPP).

---

## 📋 Requisitos Previos Verificables

```powershell
# Verificar PHP (8.x)
php -v

# Verificar Composer
composer --version

# Verificar MySQL
mysql --version

# Verificar Node.js (≥ 12)
node --version
```

| Herramienta | Descarga |
|---|---|
| **PHP 8.x** | https://windows.php.net/download |
| **Composer** | https://getcomposer.org/Composer-Setup.exe |
| **MySQL 8.x** | https://dev.mysql.com/downloads/installer/ |
| **Node.js LTS** | https://nodejs.org |

---

## 📁 Estructura de este Kit

```
Blindsystem_V4/
├── artisan                          ← Entry point de Laravel
├── iniciar.bat                      ← Script para arrancar el proyecto
├── .env                             ← Configuración de BD y entorno
├── app/
├── config/
├── public/
│   └── v/0.3.16/
│       ├── js/
│       │   ├── app.js              ← SPA compilado (~2.6 MB)
│       │   ├── Login.js            ← Login component
│       │   └── Home.js             ← Home component
│       └── css/
│           ├── app.css
│           └── materialdesignicons.css
├── routes/
├── setup/
│   ├── SETUP.md                    ← Este archivo
│   ├── patches/                    ← Archivos modificados
│   │   ├── .env.local
│   │   ├── app.js
│   │   ├── Login.js
│   │   ├── WebService.php
│   │   ├── DashboardController.php
│   │   ├── VerifyCsrfToken.php
│   │   └── CErpUserController.php
│   └── database.sql                ← Schema y datos iniciales
└── vendor/                         ← Dependencias PHP
```

---

## 🗄️ Base de Datos

El SQL dump está en una carpeta externa:

- `C:\Users\Soporte\Documents\Blindsystems_v3\Blindsystem_Archivos\LansonAllDB.sql` (67.7 MB)

El proyecto NO usa Laravel migrations. El schema se importa directamente desde el SQL dump (~119 tablas).

---

## 🚀 Paso a Paso

### PASO 1 — Ubicar el Proyecto

Copia la carpeta `Blindsystem_V4` a cualquier ubicación (ej. `C:\Users\tu-usuario\Documents\Blindsystem_V4`).

### PASO 2 — Inicializar MySQL

```powershell
# Crear la base de datos
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS lansonshades CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

IMPORTANTE: La contraseña de MySQL debe coincidir con la del `.env`.
Actualmente está configurado con `DB_USERNAME=root` y `DB_PASSWORD=root`.
Si tu MySQL tiene otra contraseña, edita `.env`.

### PASO 3 — Importar Base de Datos

```powershell
# Ajusta la ruta al SQL dump en tu máquina
$sqlFile = "C:\Users\Soporte\Documents\Blindsystems_v3\Blindsystem_Archivos\LansonAllDB.sql"

mysql -u root -proot --default-character-set=utf8mb4 lansonshades < $sqlFile
```

**Verificación:**
```powershell
mysql -u root -proot -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='lansonshades';"
```
Debe mostrar ~119 tablas.

### PASO 4 — Configurar .env

```powershell
# Copiar el template
Copy-Item "setup\patches\.env.local" ".env" -Force
```

O editar manualmente `.env` con estos valores clave:

```
APP_URL=http://localhost:8000
APP_VERSION=0.3.16

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lansonshades
DB_USERNAME=root
DB_PASSWORD=root
```

### PASO 5 — Configurar PHP

Asegúrate que `php.ini` tenga estas extensiones habilitadas:

```ini
extension=openssl
extension=pdo_mysql
extension=mbstring
extension=fileinfo
extension=sodium
extension=zip
extension=curl
```

**Verificación:**
```powershell
php -r "echo 'openssl:'.extension_loaded('openssl').' pdo_mysql:'.extension_loaded('pdo_mysql').' sodium:'.extension_loaded('sodium');"
```
Debe mostrar `openssl:1 pdo_mysql:1 sodium:1`.

### PASO 6 — Instalar Dependencias PHP (Composer)

```powershell
# Dentro de la carpeta del proyecto
composer install --no-interaction --prefer-dist --ignore-platform-req=php
```

Si falla por permisos de carpeta:
```powershell
New-Item -ItemType Directory -Path "bootstrap\cache" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\cache" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\sessions" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\views" -Force | Out-Null
```

### PASO 7 — Instalar Dependencias npm

```powershell
npm install
```

> ⚠ **ADVERTENCIA**: NO ejecutar `npm run build`, `npm run dev`, ni `npm run production`.
> El proyecto viene con assets Vue precompilados en `public/v/0.3.16/`.
> No hay código fuente Vue en `resources/js/`. Recompilar **destruye** el `app.js` funcional.

### PASO 8 — Copiar Archivos Parchados

```powershell
# Copiar archivos modificados del directorio setup/patches/ al proyecto
$patches = "setup\patches"

Copy-Item "$patches\app.js"              "public\v\0.3.16\js\app.js"              -Force
Copy-Item "$patches\Login.js"            "public\v\0.3.16\js\Login.js"            -Force
Copy-Item "$patches\WebService.php"      "app\classes\WebService.php"             -Force
Copy-Item "$patches\DashboardController.php" "app\Http\Controllers\DashboardController.php" -Force
Copy-Item "$patches\VerifyCsrfToken.php" "app\Http\Middleware\VerifyCsrfToken.php" -Force
Copy-Item "$patches\CErpUserController.php" "app\Http\Controllers\CErpUserController.php" -Force
```

**Qué hace cada parche:**

| Archivo | Fix |
|---|---|
| `app.js` | Original (2.6 MB) — restaura el Vue app completo |
| `Login.js` | Cambia Firebase por `fetch('/auth/login')` + fix spinner infinito |
| `WebService.php` | Timeout 5s + retorna `{items: []}` si API externa falla |
| `DashboardController.php` | Datos vacíos seguros para dashboards sin API externa |
| `VerifyCsrfToken.php` | Excluye `/auth/*`, `/verify/*`, `/bi/*` de CSRF |
| `CErpUserController.php` | Login por email+password directo; null check en uid path |

### PASO 9 — Generar Keys y Preparar Laravel

```powershell
# Generar llave de encriptación (escribe APP_KEY en .env)
php artisan key:generate

# Generar secreto JWT (escribe JWT_SECRET en .env)
php artisan jwt:secret

# Crear enlace simbólico para storage
php artisan storage:link

# Limpiar cachés
php artisan optimize:clear

# Verificar conexión a BD
php artisan tinker --execute="DB::connection()->getPdo(); echo 'BD OK';"
```

### PASO 10 — Resetear Contraseñas de Usuarios

El login usa contraseñas con bcrypt. Asigna una contraseña común para desarrollo:

```powershell
# Generar el hash bcrypt de la contraseña
$hash = php -r "echo password_hash('Blind001', PASSWORD_BCRYPT);"

# Actualizar todos los usuarios
mysql -u root -proot -e "UPDATE lansonshades.c_erp_users SET password='$hash', is_active=1 WHERE 1;"
```

**Verificación:**
```powershell
mysql -u root -proot -e "SELECT id, user_email, is_active FROM lansonshades.c_erp_users LIMIT 5;"
```

### PASO 11 — Arrancar el Servidor

```powershell
# Opción A: Usar el script
.\iniciar.bat

# Opción B: Manual
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir en el navegador: **http://localhost:8000**

> 💡 Si ves pantalla en blanco, presiona **Ctrl+F5** para forzar recarga sin caché.

---

## 🔑 Credenciales de Acceso

Todos los usuarios tienen la misma contraseña después del PASO 10:

```
Contraseña: Blind001
```

Usuarios principales:

| Email |
|---|
| `oscar.cortes@wrks.com.mx` |
| `veronica.villagomez@wrks.com.mx` |
| `jms@wrks.com.mx` |
| `cxc@wrks.com.mx` |

---

## 🧪 Comandos de Diagnóstico

```powershell
# Ver logs en tiempo real
Get-Content storage\logs\laravel.log -Wait

# Probar login
$body = '{"email":"oscar.cortes@wrks.com.mx","password":"Blind001"}'
curl.exe -s -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d $body

# Debe responder: {"success":true,"token":"eyJ...","firebase_uid":"...","user":{...}}
```

---

## ⚠️ Problemas Conocidos

| Síntoma | Causa | Solución |
|---|---|---|
| `could not find driver` | `pdo_mysql` no activado | Activar en `php.ini` |
| Pantalla en blanco | Caché del navegador | Ctrl+F5 para recarga forzada |
| Pantalla en blanco | `APP_VERSION` no configurado | Verificar `.env` tiene `APP_VERSION=0.3.16` |
| `app.js` de 490 KB | Se ejecutó `npm run production` | Restaurar desde `setup\patches\app.js` (2.6 MB) |
| "Page Expired" (419) | CSRF token mismatch | Asegurar que `VerifyCsrfToken.php` excluye `auth/*` |
| Spinner infinito al hacer login | Error en Login.js | Restaurar `setup\patches\Login.js` |
| `bootstrap/cache must be writable` | Permisos | Ejecutar `icacls bootstrap\cache /grant "Everyone:F" /T /Q` |
| `Target class [nombre] does not exist` | Falta composer dump | Ejecutar `composer dump-autoload` |

---

## 🏗️ Arquitectura

| Componente | Ubicación |
|---|---|
| **Proyecto Laravel** | `Blindsystem_V4\` |
| **Base de datos** | `lansonshades` en MySQL 127.0.0.1:3306 |
| **Assets Vue compilados** | `public/v/0.3.16/js/` y `public/v/0.3.16/css/` |
| **Logs** | `storage/logs/laravel.log` |
| **Servidor** | PHP built-in en `http://localhost:8000` |

### Flujo de Datos

- **Módulos operativos** (órdenes, cotizaciones, inventario, etc.): → MySQL local
- **Dashboards financieros** (Home, BI D1-D9): → API externa `http://aspelroller3.ddns.net:81/api/v1/...`. En local la API externa no responde → los parches proveen datos vacíos seguros.

---

## 📦 Dependencias Clave

| Paquete | Versión | Nota |
|---|---|---|
| PHP | 8.4.21 | Probado con built-in server |
| Laravel | 8.83.27 | |
| JWT Auth | dev-develop | `tymon/jwt-auth` |
| Vue | 2.7.14 | Compilado con Laravel Mix 6 |
| Vuetify | 2.6.15 | Material Design UI |
| MySQL | 8.x | |
