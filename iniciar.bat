@echo off
title Blindsystem ERP System v 1.0
color 0A

cls
echo ===============================================================================
echo                      Blindsystem ERP System v 1.0
echo ===============================================================================
echo.
echo  Framework:   Laravel 8 + Vue 2 + Vuetify 2
echo  Servidor:    http://localhost:8000
echo  Base datos:  lansonshades (MySQL)
echo  Carpeta:     %~dp0
echo.
echo ===============================================================================
echo.
echo  Iniciando servidor PHP...
echo.

cd /d "%~dp0"
echo  [%date% %time%] Servidor corriendo en http://localhost:8000
echo.
start http://localhost:8000
php artisan serve --host=127.0.0.1 --port=8000

echo.
echo  Cerrando servidor...
pause
