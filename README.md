# Blindsystem ERP System v 1.0

Sistema ERP para la gestión de persianas y cortinas. Construido con **Laravel 8 + Vue 2 + Vuetify 2**.

## Requisitos

- PHP ^7.3|^8.0
- Composer
- Node.js >= 12
- MySQL 8.x

## Instalación rápida

```powershell
cd C:\laragon\www\lansonshades
composer install --ignore-platform-req=php
npm install
php artisan key:generate
php artisan jwt:secret
php artisan storage:link
```

1. Importar la base de datos (`setup/LansonAllDB.sql`) a MySQL.
2. Copiar `setup/patches/.env.local` como `.env` y ajustar credenciales.
3. Ejecutar `iniciar.bat` o `php artisan serve --port=8000`.
4. Abrir http://localhost:8000

> **NO** ejecutar `npm run dev/production` — los assets Vue están precompilados en `public/v/0.3.16/js/`.

## Credenciales por defecto

Todos los usuarios usan contraseña: `Blind001`

## Archivos clave

| Archivo | Propósito |
|---|---|
| `iniciar.bat` | Inicia el servidor PHP con un clic |
| `setup/SETUP.md` | Guía detallada de instalación |
| `setup/patches/` | Parches para entorno local (login sin Firebase, etc.) |
| `SISTEMA.md` | Documentación completa del sistema |
