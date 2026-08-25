# SISTEMA.md — Blindsystem ERP System v 1.0

## Descripción general

ERP web para la gestión integral de fabricación y venta de persianas, cortinas y toldos.  
Marca principal: **Lanson Shades** (LS). También integra datos de **Rollertex** (RT) y **Lanson Beckman** (LB).

## Stack tecnológico

| Componente | Tecnología |
|---|---|
| Backend | Laravel 8.83.27 (PHP 7.3-8.0) |
| Frontend | Vue 2.7.14 + Vuetify 2.6.15 |
| Compilación | Laravel Mix 6 (Webpack) |
| Base de datos | MySQL 8.x (119 tablas) |
| Autenticación | JWT (tymon/jwt-auth) + Firebase (UID) |
| UI/UX | Sweetalert2, Vue2Editor, Vuetify, Chart.js |
| Exportación | Laravel Excel (Maatwebsite), FPDF |
| Tiempo real | Socket.io, Laravel Echo |

## Estructura del proyecto

```
Blindsystem_V4/
├── app/
│   ├── classes/           # Clases de apoyo (Logs, WebService, GetTotal, etc.)
│   ├── Console/           # Comandos Artisan personalizados
│   ├── Exceptions/        # Manejadores de excepción
│   ├── Exports/           # Exportaciones a Excel
│   ├── fpdf/              # Generación de PDFs
│   ├── Helpers/           # Funciones helper globales
│   ├── Http/
│   │   ├── Controllers/   # 109 controladores (API REST)
│   │   │   └── BI/        # Controladores de dashboards BI
│   │   └── Middleware/     # Middleware (CORS, CSRF, JWT, etc.)
│   ├── Imports/           # Importaciones desde Excel
│   ├── Mail/              # Clases de correo
│   ├── Models/            # 101 modelos Eloquent
│   └── Providers/         # Service Providers
├── bootstrap/             # Archivos de arranque de Laravel
├── config/                # 17 archivos de configuración
├── database/
│   ├── factories/         # Factory para seeds
│   ├── migrations/        # 4 migraciones base (users, tokens, etc.)
│   └── seeders/           # DatabaseSeeder
├── public/
│   ├── fonts/             # Tipografías (Material Design Icons)
│   ├── img/               # Imágenes del sistema (logos, usuarios, artículos, clientes, config)
│   ├── sounds/            # Sonidos (beep, fail)
│   ├── storage/           # Symlink a storage/app/public
│   └── v/0.3.16/         # Assets Vue compilados (JS + CSS + fuentes)
├── resources/
│   ├── css/               # Estilos base
│   ├── js/                # Stubs de JS (no contiene código fuente Vue)
│   ├── lang/en/           # Traducciones al inglés
│   ├── plugins/           # Plugins de Vue (vuetify, editor, splide)
│   └── views/             # Blade views (app.blade.php + mails)
├── routes/                # web.php, api.php, channels.php, console.php
├── setup/
│   ├── patches/           # Parches para entorno local (login sin Firebase, etc.)
│   └── SETUP.md           # Guía detallada de instalación
├── storage/               # Logs, caché, sesiones, archivos subidos
├── tests/                 # Tests unitarios y de feature
├── vendor/                # Dependencias PHP (Composer)
└── .env                   # Variables de entorno
```

## Modelo de datos (Modelos principales)

### Catálogos (prefijo `C_`)
- `CArticle` — Artículos/productos base
- `CCategory` — Categorías de artículos
- `CColor` — Colores
- `CCompany` — Empresas (LS, RT, LB)
- `CDepartment` — Departamentos
- `CModel` — Modelos de artículos
- `CProduct` — Tipos de producto (persiana, toldo, etc.)
- `CUnit` — Unidades de medida
- `CUser` — Usuarios del sistema
- `CErpUser` — Usuarios del ERP
- `CProvider` — Proveedores
- `CChain` — Tipos de cadena
- `CMechanism` / `CMechanismSide` — Mecanismos
- `CTube` — Tubos
- `CCounterweightBar` — Contrapesos
- `CTypeMotor` / `CConfigMotors` — Motores

### Transacciones (prefijo `D_`)
- `DOrder` — Órdenes de producción
- `DQuotation` — Cotizaciones
- `DPurchase` — Compras
- `DInventory` — Inventario
- `DSection` — Secciones de producción
- `DMovement` — Movimientos
- `DGuaranty` — Garantías
- `DExternalInvoice` — Facturas externas
- `DMaterialRequest` — Solicitudes de material
- `DProductionLine` — Líneas de producción
- `DComplement` — Complementos
- `DWarehouseLocation` — Ubicaciones de almacén
- `DTempOrder` — Órdenes temporales

### Entidades (prefijo `E_`)
- `ELead` — Prospectos/clientes potenciales
- `EOrder` — Órdenes (entidad)
- `EQuotation` — Cotizaciones (entidad)
- `ESection` — Secciones (entidad)
- `EInventory` — Inventario (entidad)
- `ESchedule` — Agendamiento
- `ENotification` — Notificaciones
- `EGuaranty` — Garantías (entidad)
- `EPurchase` — Compras (entidad)

### Usuarios y permisos
- `CErpUser` — Usuarios internos del ERP
- `CErpInfoUser` — Información detallada del usuario
- `DErpAccessUser` — Permisos módulo/submódulo por usuario
- `CErpModule` / `CErpSubmodule` — Módulos y submódulos del menú
- `CDashboardPermission` — Permisos de dashboards
- `DAccessUser` — Accesos genéricos

## Roles y permisos

El sistema maneja permisos por **módulos y submódulos** asignados a cada usuario.

| Campo en CErpInfoUser | Descripción |
|---|---|
| `is_leader` | Usuario líder (1/0) |
| `is_agent` | Usuario agente (1/0) |
| `department_id` | Departamento al que pertenece |
| `company_id` | Empresa a la que pertenece |

Los accesos se almacenan en `d_erp_access_users` con:
- `module_id` → ID del módulo
- `submodule_id` → ID del submódulo (0 si es módulo padre)
- `submodule_son_id` → ID del submódulo hijo (0 si no aplica)

### Módulos del sistema
1. Dashboard / Home
2. Usuarios
3. Clientes
4. Cotizaciones
5. Órdenes
6. Producción
7. Inventario / Almacén
8. Compras
9. Garantías
10. Envíos
11. Reportes BI
12. Configuración

## Autenticación

### Flujo normal (producción)
1. Login vía Firebase (`signInWithEmailAndPassword`)
2. Firebase devuelve UID
3. Se envía UID al backend → JWT
4. Se validan módulos a los que tiene acceso

### Flujo local (desarrollo)
1. Login directo por `email + password` al endpoint `/auth/login`
2. Backend verifica contra `c_erp_users.password` (bcrypt)
3. Genera JWT y devuelve módulos de acceso

Los parches en `setup/patches/` habilitan el flujo local.

## Dashboards y BI

Los dashboards financieros consultan una API externa:
- `http://aspelroller3.ddns.net:81/api/v1/...` (ASPEL)

En entorno local esta API no responde. Los controladores parchados devuelven datos vacíos seguros.

Paneles disponibles:
- **Home** — Resumen financiero (LS, RT, LB)
- **D1-D9** — Dashboards de BI (cartera vencida, productividad, etc.)

## API externa (WebService)

Clase `app/classes/WebService.php` que consume:
- `getDataLBDetails()` — Detalle Lanson Beckman
- `getDataRTDetails()` — Detalle Rollertex
- `getDataLSDetails()` — Detalle Lanson Shades
- `getDataCVRT()` — Cartera vencida RT
- `getDataCVLB()` — Cartera vencida LB
- `getDataLSInvoicesDate()` — Facturas LS por fecha

## Versiones anteriores (archivo)

Las versiones anteriores de assets Vue están en:
- `public/v/0.3.13/`
- `public/v/0.3.14/`
- `public/v/0.3.15/`

Solo `0.3.16` es la versión activa y debe mantenerse.

## Usuarios de prueba

Contraseña general para desarrollo: `Blind001`

| Email | Rol |
|---|---|
| oscar.cortes@wrks.com.mx | Admin / Líder |
| veronica.villagomez@wrks.com.mx | Admin |
| jms@wrks.com.mx | Usuario general |
| cxc@wrks.com.mx | Cuentas por cobrar |

## Comandos útiles

```powershell
# Iniciar servidor
php artisan serve --host=127.0.0.1 --port=8000

# Ver logs
Get-Content storage/logs/laravel.log -Wait

# Ver rutas
php artisan route:list

# Limpiar caché
php artisan optimize:clear

# Verificar BD
php artisan tinker --execute="DB::connection()->getPdo(); echo 'BD OK';"
```

## Problemas comunes

| Problema | Solución |
|---|---|
| Pantalla en blanco | Verificar `APP_VERSION=0.3.16` en `.env` |
| app.js de 490KB | Restaurar desde `setup/patches/app.js` (2.6MB) |
| 419 Page Expired | CSRF — aplicar parche `VerifyCsrfToken.php` |
| "could not find driver" | Activar `pdo_mysql` en `php.ini` |
| API externa no responde | Usar parches de `setup/patches/` |
