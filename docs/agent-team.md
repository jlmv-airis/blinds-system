# Equipo de agentes — Migración Blindsystem a Neubox

Diseñado a partir de los hallazgos reales de `scan-findings.md`, no genérico. Cada agente ataca un problema concreto encontrado en el escaneo. Viven en [`.claude/agents/`](../.claude/agents/) y se invocan desde Claude Code en este repo.

## Los 6 agentes

| Agente | Ataca | Por qué existe |
|---|---|---|
| [`security-hardening`](../.claude/agents/security-hardening.md) | Rutas sin auth, credenciales hardcodeadas, archivos de debug | Hallazgo crítico §1-3 del escaneo — bloqueante antes de exponer el sistema en la nube |
| [`db-schema-migrator`](../.claude/agents/db-schema-migrator.md) | Esquema de BD sin versionar (119 tablas solo en dump SQL externo), modelo multi-marca LS/RT/LB | No hay migraciones reales; hay que reconstruirlas para tener despliegues reproducibles |
| [`laravel-cloud-adapter`](../.claude/agents/laravel-cloud-adapter.md) | Config de Laravel para nube: env vars, storage (S3), queue, cache, logging, `APP_ENV`/`APP_DEBUG` | El `.env` actual está configurado como local (`APP_DEBUG=true`) y debe adaptarse a un entorno cloud real en Neubox |
| [`frontend-build-engineer`](../.claude/agents/frontend-build-engineer.md) | Pipeline de build de Vue/Vuetify roto (assets precompilados a mano, JS crudo embebido en Blade) | El README prohíbe `npm run build` — no hay build reproducible hoy |
| [`integration-specialist`](../.claude/agents/integration-specialist.md) | API externa ASPEL (DDNS), Firebase Auth, Pusher/socket.io | Dependencias de red externas que deben seguir funcionando (o migrarse) desde Neubox |
| [`deploy-ops`](../.claude/agents/deploy-ops.md) | Despliegue real en Neubox: procesos (PHP-FPM, queue worker, socket server), rollback, checklist | Última milla — llevar todo lo anterior a un ambiente vivo |

## Orden de trabajo sugerido

1. **`db-schema-migrator`** primero — sin esquema versionado no hay nada reproducible que desplegar.
2. **`security-hardening`** — cerrar los huecos antes de que el sistema sea accesible desde internet.
3. **`laravel-cloud-adapter`** y **`frontend-build-engineer`** en paralelo — no dependen entre sí.
4. **`integration-specialist`** — una vez que la app corre en un entorno cloud-ready, validar que ASPEL/Firebase/sockets sigan funcionando desde ahí.
5. **`deploy-ops`** al final — orquesta el despliegue real usando el trabajo de todos los anteriores.

## Qué NO se automatizó

- **Conseguir el dump `LansonAllDB.sql` real** — eso depende de una persona con acceso al servidor Windows original, ningún agente puede resolverlo.
- **Decisión de negocio sobre las 3 marcas** (¿un solo despliegue multi-empresa o se separan?) — se documenta la pregunta en `db-schema-migrator`, pero la decide el usuario.
- **Credenciales nuevas de Neubox** (accesos, tipo de servicio contratado) — sigue pendiente que el usuario lo confirme.
