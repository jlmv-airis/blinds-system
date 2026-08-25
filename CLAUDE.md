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
- ~~El dominio `blinds-system.com` resuelve hoy públicamente a una IP distinta (`74.119.239.234`)~~ — **el usuario confirmó (2026-08-25) que no hay servidor de producción real en ningún lado**: "en el servidor está desde cero, no hay nada; lo que está en el código es lo único que hay". No existe `LansonAllDB.sql` en ningún origen — se cancela la búsqueda de un dump real. **El proyecto pasa de "migrar datos en vivo" a "reconstruir desde cero, por etapas, en Neubox"**, usando el código como única fuente de verdad (esquema inferido de los modelos Eloquent, no de un dump).
- **Verificado (2026-08-25) que el vhost de Neubox ya funciona**: se subió un archivo de prueba a `dev@blinds-system.com` y, pegándole directo a la IP de Neubox (`72.249.60.254`) con el Host/SNI correcto, lo sirve bien por HTTPS (nginx, redirect HTTP→HTTPS, headers de seguridad configurados). Confirma que el servidor destino está listo — solo falta el corte de DNS cuando el código esté listo para producción, y el equipo aún no ha decidido cuándo hacerlo.
- **Pendiente antes de subir a `dev@blinds-system.com`**: aplicar al menos la Fase 1 del plan de limpieza y quitar los archivos de debug/credenciales (ver `docs/scan-findings.md` hallazgos críticos) — subir el código tal cual expondría en vivo endpoints sin autenticación y credenciales reales.

## Etapas de reconstrucción (post-pivote 2026-08-25)

Ya no hay datos reales que migrar — se reconstruye por etapas, cada una en su propia rama de git:

1. **`etapa-1-limpieza-seguridad`** (en curso): Fase 1 del plan de pulido (`docs/cleanup-plan.md`) — bug P0, controladores huérfanos, `.bak`, archivos de debug/credenciales, `.env` nuevo sin secretos reutilizados. No depende de base de datos.
2. **Reconstrucción de esquema**: inferir el esquema real (~119 tablas) desde `app/Models/` (prefijos C_/D_/E_) y los fragmentos SQL sueltos (`app/classes/sp*`), ya que no hay dump — no será perfecto sin un DBA real revisando, se itera con lo que surja en QA.
3. **Deploy base a Neubox**: código limpio + BD nueva vacía con migraciones reconstruidas, apuntando a `dev@blinds-system.com`.
4. **Datos mínimos**: sembrar catálogos base (empresas LS/RT/LB, al menos un usuario admin) — no hay datos históricos que recuperar.
5. **QA funcional por módulo** (`docs/qa-test-plan.md` Fase 2) contra ese ambiente ya desplegado, arreglando lo que se rompa.
6. **Decisiones de producto pendientes** (`docs/cleanup-plan.md` Fase 3): módulo Configuración (catálogos nunca implementados), los 2 hacks runtime (Artículos/Inventario Local) — formalizar o descartar.

## Reglas de trabajo

- No asumir stack, framework ni arquitectura sin confirmarlo en el código real (ya se hizo para V5.3/5.4 — cualquier versión nueva que llegue debe re-escanearse, no asumir que es igual).
- Todo lo que llega a `incoming/` (código legacy, `.env` reales, dumps) nunca se sube a git — ver `.gitignore`. Solo `incoming/README.md` se trackea.
- Cualquier credencial encontrada en el código legacy (APP_KEY, JWT_SECRET, credenciales Firebird/MySQL) se trata como comprometida y debe rotarse en el ambiente nuevo — nunca reutilizar valores del local en Neubox.
- Mantener este repo fuera de carpetas sincronizadas por Google Drive/Dropbox — evita corrupción de `.git` por conflictos de sincronización.
- Antes de cualquier despliegue real, ejecutar/consultar `security-hardening` y `deploy-ops` (ver `.claude/agents/`) — no desplegar directo sin pasar por el checklist.
