# Plan de migración de base de datos — Blindsystem a Neubox

Objetivo: llevar la base de datos MySQL real (~119 tablas, datos de LS/RT/LB) de donde vive hoy a Neubox, **manteniéndola intacta** (sin pérdida de datos, sin cambios de esquema no solicitados).

## Estado actual — qué falta antes de poder ejecutar esto

No se puede ejecutar ningún paso de este plan todavía porque:
1. **No tenemos el dump real** (`LansonAllDB.sql`, ~67.7MB según `SETUP.md`, nunca incluido en ninguna versión extraída del código).
2. **No hay acceso confirmado al servidor de producción real** (`74.119.239.234`) — probamos conectividad desde este entorno y no respondió; puede ser un firewall que solo permite ciertas IPs, o simplemente que este entorno no tiene salida a esa red.
3. **No sabemos todavía qué ofrece exactamente la cuenta de Neubox para bases de datos** — necesitamos entrar al panel de control (probablemente cPanel o similar, dado que el FTP es Pure-FTPd) y confirmar: ¿MySQL incluido?, ¿acceso a phpMyAdmin?, ¿límite de tamaño de subida de phpMyAdmin (default suele ser 50MB, y el dump son ~67MB — puede requerir subir por partes o por otra vía)?, ¿se permite conexión remota directa al puerto 3306?

## Pasos del plan (una vez desbloqueados los 3 puntos anteriores)

### 1. Exportar el dump desde el origen real
Si se consigue acceso SSH al servidor de producción:
```bash
mysqldump --single-transaction --routines --triggers \
  -u <usuario> -p <basededatos> > LansonAllDB_$(date +%Y%m%d).sql
```
`--single-transaction` es importante: evita bloquear las tablas mientras el negocio sigue operando con el sistema en vivo. Si no hay acceso SSH y solo existe el archivo `LansonAllDB.sql` ya generado en algún equipo (según `SETUP.md`, hay una copia en una máquina Windows), simplemente se transfiere ese archivo tal cual.

### 2. Transferir el dump de forma segura
- Preferir SFTP/SCP o el mismo FTPS que ya usamos (nunca FTP plano) para no exponer el dump completo (con datos reales de clientes) en tránsito sin cifrar.
- Comprimir antes de transferir (`gzip`) — un dump de 67MB de texto SQL suele comprimir a una fracción de su tamaño, acelera la subida.

### 3. Provisionar la base de datos en Neubox
- Crear la base de datos y un usuario MySQL **nuevo** desde el panel de Neubox (nunca reutilizar `root`/`root` ni las credenciales del `.env` local, ambas ya marcadas como comprometidas en el escaneo de seguridad).
- Confirmar el charset/collation al crear la BD — dado que es una app en español con nombres/direcciones con acentos, debe coincidir con lo que use el dump original (comúnmente `utf8mb4`) para no corromper texto.

### 4. Importar
- Si el dump cabe dentro del límite de subida de phpMyAdmin: importar directo ahí.
- Si no cabe (67MB frecuentemente excede el límite default): usar el import por línea de comandos si el panel da acceso SSH, o dividir el dump en partes, o revisar si el panel de Neubox tiene una herramienta de import de archivos grandes (común en cPanel vía "phpMyAdmin > Import > desde archivo del servidor" en vez de subida directa por navegador).

### 5. Apuntar la app a la nueva base de datos
- Actualizar `.env` en Neubox con las credenciales nuevas (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — normalmente `DB_HOST=localhost` en hosting compartido, a confirmar).
- Probar conexión: `php artisan tinker --execute="DB::connection()->getPdo(); echo 'BD OK';"` (comando que ya está documentado en `SISTEMA.md` del propio proyecto).

### 6. Verificar integridad de la migración
- Comparar conteo de filas por tabla entre origen y destino (`SELECT COUNT(*) FROM <tabla>` en ambos lados, para las tablas más críticas al menos: usuarios, órdenes, cotizaciones, artículos).
- Confirmar que los 3 companies (LS/RT/LB) siguen presentes con sus datos relacionados intactos.
- Correr el smoke test funcional de `docs/qa-test-plan.md` Fase 2 contra esta base ya importada.

### 7. Plan de corte con mínima interrupción
La base de datos original sigue recibiendo transacciones reales del negocio mientras se prepara todo esto. Recomendación:
1. Dump inicial + import a Neubox → usarlo solo para pruebas (Fase 2-3 de QA), sin apuntar tráfico real todavía.
2. Cuando todo esté validado, coordinar una **ventana de corte corta**: pausar escrituras en el sistema original (o tomar un segundo dump incremental de los cambios entre el primero y el momento del corte), importar ese diferencial a Neubox, y recién ahí cambiar el DNS.
3. Mantener el servidor/BD original **intacto y accesible** durante un periodo de gracia después del corte, por si hay que revertir el DNS rápidamente ante cualquier problema.

## Explícitamente fuera de este plan

- No se propone ningún cambio de esquema (nuevas tablas, columnas, tipos) — el objetivo es mover los datos tal cual están, no rediseñar la base.
- No se decide aquí si las 3 marcas se separan en el futuro — eso ya quedó como pregunta abierta en `docs/agent-team.md`, y no bloquea esta migración (se mueve todo junto, como está hoy).

## Lo que necesito de ti para poder avanzar

1. Acceso real (SSH o al menos un método de descarga) al servidor donde vive hoy el dump o la base de datos en vivo.
2. Entrar al panel de control de Neubox y decirme qué opciones de base de datos ves ahí (MySQL, phpMyAdmin, límites de subida, si hay SSH) — con eso afino los pasos 3 y 4 a algo ejecutable en vez de genérico.
