# Blinds System — Migración a Nube (Neubox)

## Contexto del proyecto

Se está migrando a la nube una plataforma local de venta de cortinas/persianas.

- **Empresa anterior:** Lanson — dueña original del sistema.
- **Empresa actual:** Blindsystem — heredó el sistema; solo se adaptaron los servicios existentes de Lanson para operar bajo la nueva marca (branding, datos, posiblemente dominios/credenciales), sin un rediseño completo.
- **Destino de despliegue:** Neubox.
- **Repositorio de trabajo:** este repo (`~/Documents/GitHub/blinds-system`), en GitHub como [`jlmv-airis/blinds-system`](https://github.com/jlmv-airis/blinds-system) (privado).
- **Rol del usuario:** developer, dueño técnico de la migración.

## Estado actual (2026-08-25)

- Aún no se ha recibido el código fuente de la plataforma local. Se está en espera del **zip con todo el código** que corre localmente.
- El stack tecnológico de la plataforma actual **todavía no se conoce** — se determinará al escanear el código una vez recibido.
- No se ha decidido aún la estrategia de migración (rehost / replatform / rearchitect) — depende de lo que se encuentre en el escaneo.

## Cómo continuar cuando llegue el zip

1. El usuario coloca el .zip en [`incoming/`](incoming/).
2. Descomprimir en `incoming/` (o en una subcarpeta con el nombre del zip) y escanear el código: stack, framework, base de datos, dependencias externas, integraciones de pago/envío, estructura de módulos, artefactos con nombre "Lanson" remanente.
3. Con el resultado del escaneo, documentar hallazgos en `docs/`.
4. **Solo después del escaneo**, diseñar el equipo de subagentes (`.claude/agents/`) especializado para el levantamiento del servicio en Neubox — no crear agentes antes de tener este contexto real.
5. Definir plan de migración y estructura final del repo con base en lo anterior.

## Reglas de trabajo

- No asumir stack, framework ni arquitectura hasta confirmarlo en el código real.
- No diseñar agentes ni planeación detallada de migración antes de completar el escaneo de código.
- Mantener este repo fuera de carpetas sincronizadas por Google Drive/Dropbox — evita corrupción de `.git` por conflictos de sincronización.
