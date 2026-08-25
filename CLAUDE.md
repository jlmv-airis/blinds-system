# Blinds System — Migración a Nube (Neubox)

## Contexto del proyecto

Se está migrando a la nube un ERP local de fabricación/venta de persianas, cortinas y toldos.

- **Empresa anterior:** Lanson — dueña original del sistema (marca "Lanson Shades").
- **Empresa actual:** Blindsystem — nombre nuevo del ERP en sí. El sistema es **multi-marca**: sigue operando Lanson Shades (LS), Rollertex (RT) y Lanson Beckman (LB) bajo una sola base de datos (`CCompany`). "Lanson" no desapareció, sigue siendo una de las marcas activas — ver `docs/scan-findings.md`.
- **Destino de despliegue:** Neubox.
- **Repositorio de trabajo:** este repo (`~/Documents/GitHub/blinds-system`), en GitHub como [`jlmv-airis/blinds-system`](https://github.com/jlmv-airis/blinds-system) (privado).
- **Rol del usuario:** developer, dueño técnico de la migración.

## Estado actual (2026-08-25)

- Código fuente recibido en `incoming/` (Laravel 8 + Vue 2 + Vuetify 2 + Node socket server). Versión vigente en producción confirmada por el usuario: `Blindsystem_5.4` (idéntica byte a byte a `Blindsystem_V5.3` — ver nota en `docs/scan-findings.md`).
- Escaneo completo realizado — hallazgos en [`docs/scan-findings.md`](docs/scan-findings.md), incluyendo problemas de seguridad críticos (rutas sin autenticación, credenciales hardcodeadas, `.env` de producción con `APP_DEBUG=true`) y bloqueantes (falta el dump real de base de datos `LansonAllDB.sql`).
- Equipo de agentes diseñado en [`.claude/agents/`](.claude/agents/) — ver [`docs/agent-team.md`](docs/agent-team.md) para el rol de cada uno y el orden de trabajo sugerido.
- Auditoría de módulos completa — [`docs/cleanup-plan.md`](docs/cleanup-plan.md): 54/112 controladores huérfanos, bug P0 activo (`dd()` en `DashboardInventoryController.php:24`), plan de limpieza por fases (todo a nivel código, sin tocar BD).
- **Destino de despliegue confirmado**: cuenta FTP `dev@blinds-system.com` en Neubox (servidor `svgt380.serverneubox.com.mx`, IP `72.249.60.254`). Confirmada vacía (solo scaffolding de hosting + certificado SSL en trámite) — es el destino nuevo, no el origen. El usuario planea subir el código ahí directamente.
- El dominio `blinds-system.com` resuelve hoy públicamente a una IP distinta (`74.119.239.234`) — probable ubicación del servidor de producción real, donde vive el sitio que usan los clientes hoy. Aún sin acceso confirmado ahí (intentos de conectividad desde este entorno hacia esa IP fallaron por timeout). Sigue pendiente conseguir ahí el `.env` real y el dump `LansonAllDB.sql`.
- **Verificado (2026-08-25) que el vhost de Neubox ya funciona**: se subió un archivo de prueba a `dev@blinds-system.com` y, pegándole directo a la IP de Neubox (`72.249.60.254`) con el Host/SNI correcto, lo sirve bien por HTTPS (nginx, redirect HTTP→HTTPS, headers de seguridad configurados). Confirma que el servidor destino está listo — solo falta el corte de DNS cuando el código esté listo para producción, y el equipo aún no ha decidido cuándo hacerlo.
- **Pendiente antes de subir a `dev@blinds-system.com`**: aplicar al menos la Fase 1 del plan de limpieza y quitar los archivos de debug/credenciales (ver `docs/scan-findings.md` hallazgos críticos) — subir el código tal cual expondría en vivo endpoints sin autenticación y credenciales reales.

## Reglas de trabajo

- No asumir stack, framework ni arquitectura sin confirmarlo en el código real (ya se hizo para V5.3/5.4 — cualquier versión nueva que llegue debe re-escanearse, no asumir que es igual).
- Todo lo que llega a `incoming/` (código legacy, `.env` reales, dumps) nunca se sube a git — ver `.gitignore`. Solo `incoming/README.md` se trackea.
- Cualquier credencial encontrada en el código legacy (APP_KEY, JWT_SECRET, credenciales Firebird/MySQL) se trata como comprometida y debe rotarse en el ambiente nuevo — nunca reutilizar valores del local en Neubox.
- Mantener este repo fuera de carpetas sincronizadas por Google Drive/Dropbox — evita corrupción de `.git` por conflictos de sincronización.
- Antes de cualquier despliegue real, ejecutar/consultar `security-hardening` y `deploy-ops` (ver `.claude/agents/`) — no desplegar directo sin pasar por el checklist.
