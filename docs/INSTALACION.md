# Manual de instalación: Portal de Proveedores + Supply Relay

Este manual cubre los dos proyectos:

| Proyecto | Carpeta | Qué es | Tecnología |
|---|---|---|---|
| **Portal de Proveedores** | `C:\dev-ykm\YSP` | Sitio donde los proveedores ven el calendario y descargan etiquetas e instrucción de entrega | Laravel 12, PHP 8.4, PostgreSQL |
| **Supply Relay** | `C:\dev-ykm\supply-relay` | Servicio interno que lee Infor LX y envía los datos al portal | Python 3.12, FastAPI |

```
RED INTERNA                                         PORTAL
┌──────────┐  ODBC  ┌──────────────┐  HTTPS firmado  ┌──────────────────┐
│ Infor LX │ ─────► │ Supply Relay │ ──────────────► │ /api/sync/...    │
└──────────┘        └──────────────┘   solo salida   │ Laravel + BD     │
                                                     └──────────────────┘
```

El portal **nunca** se conecta a Infor. El relay le envía los datos firmados con una llave compartida (`SYNC_KEY`), que debe ser **la misma** en los dos `.env`.

**Contenido**

1. [En tu computadora (desarrollo)](#1-en-tu-computadora-desarrollo)
2. [En el servidor Windows con IIS (producción)](#2-en-el-servidor-windows-con-iis-producción)
3. [Actualizar a una versión nueva](#3-actualizar-a-una-versión-nueva)
4. [Respaldos](#4-respaldos)
5. [Problemas comunes](#5-problemas-comunes)

---

## 1. En tu computadora (desarrollo)

### 1.1 Requisitos

| Programa | Versión | Nota |
|---|---|---|
| PHP | **8.4** (x64, NTS) | No sirve 8.2 ni 8.3: algunos paquetes del `composer.lock` piden 8.4. Ver 1.2 |
| Composer | 2.x | |
| Node.js | 20 o más | Solo para compilar los estilos y scripts (`npm run build`) |
| PostgreSQL | 16 o más | |
| Python | 3.12 | Para Supply Relay |
| Controlador ODBC de IBM i Access (64 bits) | | Solo si el relay va a leer Infor desde tu computadora |

### 1.2 PHP 8.4 portable

No hace falta instalar PHP en el sistema. Basta con descomprimirlo en una carpeta:

1. Descarga el zip **VS17 x64 Non Thread Safe** de PHP 8.4 desde <https://windows.php.net/download/> y descomprímelo en `C:\dev-ykm\tools\php84`.
2. Copia `php.ini-production` como `php.ini` y en ese archivo:
   - Quita el `;` de estas líneas: `extension_dir = "ext"`, `extension=curl`, `extension=fileinfo`, `extension=gd`, `extension=intl`, `extension=mbstring`, `extension=openssl`, `extension=pdo_pgsql`, `extension=pgsql`, `extension=pdo_sqlite`, `extension=sqlite3`, `extension=zip`.
   - Cambia estos valores:
     ```ini
     memory_limit = 512M
     post_max_size = 64M        ; un proveedor grande manda toda su información en un solo envío
     upload_max_filesize = 64M
     date.timezone = America/Mexico_City
     ```
3. Comprueba: `C:\dev-ykm\tools\php84\php.exe -v` debe decir `PHP 8.4.x`.

> En este equipo ya quedó instalado así, en `C:\dev-ykm\tools\php84`.

En los comandos de este manual, `php` significa `C:\dev-ykm\tools\php84\php.exe`. Puedes escribir la ruta completa o poner esa carpeta al inicio de la variable `PATH`. Ojo: el PHP de XAMPP (`C:\xampp\php`) es 8.2 y no sirve.

### 1.3 Base de datos

En PostgreSQL (pgAdmin o `psql`), crea un usuario y una base de datos:

```sql
CREATE ROLE portal LOGIN PASSWORD 'una-contraseña';
CREATE DATABASE portal OWNER portal;
```

### 1.4 Portal (primera vez)

```powershell
cd C:\dev-ykm\YSP
php C:\ProgramData\ComposerSetup\bin\composer.phar install
copy .env.example .env
php artisan key:generate
```

Edita el `.env`:

```ini
APP_URL=http://127.0.0.1:8000
APP_LOCALE=es
APP_TIMEZONE=America/Mexico_City

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=portal
DB_USERNAME=portal
DB_PASSWORD=una-contraseña

SESSION_DRIVER=database
QUEUE_CONNECTION=database
MAIL_MAILER=log            ; en local los correos se escriben en storage/logs/laravel.log

SYNC_KEY=                  ; la misma llave que en el relay (ver 1.5)
PORTAL_CALENDAR_FACILITY=YK1
PORTAL_CALENDAR_WORK_CENTER=   ; el centro de trabajo del calendario de planta (ver README del relay)
```

Crea las tablas, compila los estilos y crea el primer administrador:

```powershell
php artisan migrate
npm ci
npm run build
php artisan tinker --execute='$u = new App\Models\User(["name" => "Administrador", "email" => "admin@ykm.com.mx", "password" => "CambiaEstaContraseña1"]); $u->role = "admin"; $u->email_verified_at = now(); $u->save();'
```

> El comando de `tinker` va con comillas simples por fuera para que PowerShell no toque los `$`. Después, los demás usuarios se crean desde la pantalla **Usuarios** del portal.

### 1.5 Supply Relay (primera vez)

```powershell
cd C:\dev-ykm\supply-relay
py -3.12 -m venv .venv
.venv\Scripts\python.exe -m pip install -r requirements.txt
copy .env.example .env
```

Genera las llaves y el hash de la contraseña del administrador:

```powershell
.venv\Scripts\python.exe -c "import secrets; print(secrets.token_hex(32))"   # para SYNC_KEY
.venv\Scripts\python.exe -c "import secrets; print(secrets.token_hex(32))"   # para SECRET_KEY (otra distinta)
.venv\Scripts\python.exe -m relay.passwd                                     # para ADMIN_PASSWORD_HASH
```

Edita el `.env` del relay:

```ini
INFOR_DSN="Driver={iSeries Access ODBC Driver};System=192.168.200.7;"
INFOR_USERNAME=            ; usuario de Infor de SOLO LECTURA
INFOR_PASSWORD=

PORTAL_URL=http://127.0.0.1:8000
SYNC_KEY=                  ; la misma que pusiste en el .env del portal
CALENDAR_FACILITY=YK1
CALENDAR_WORK_CENTER=      ; el mismo que PORTAL_CALENDAR_WORK_CENTER

ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=       ; lo que imprimió relay.passwd
SECRET_KEY=
SECURE_COOKIES=false       ; en local no hay HTTPS; en el servidor va en true
```

### 1.6 Arrancar los dos (cada día)

Abre **dos ventanas** de PowerShell.

**Ventana 1: portal**, en <http://127.0.0.1:8000>

```powershell
cd C:\dev-ykm\YSP
C:\dev-ykm\tools\php84\php.exe artisan serve --host=127.0.0.1 --port=8000
```

**Ventana 2: Supply Relay**, en <http://127.0.0.1:8100>

```powershell
cd C:\dev-ykm\supply-relay
.venv\Scripts\python.exe -m uvicorn relay.web:create_app --factory --host 127.0.0.1 --port 8100
```

**Ventana 3: programador del relay**: ejecuta la sincronización en los días y horas de la pestaña **Programación**.

```powershell
cd C:\dev-ykm\supply-relay
.venv\Scripts\python.exe -m relay.scheduler --work
```

Para detenerlos, presiona `Ctrl + C` en cada ventana.

Opcional, en una **tercera ventana**: los correos de aviso de inicio de sesión se mandan en segundo plano. Para que salgan en local:

```powershell
cd C:\dev-ykm\YSP
C:\dev-ykm\tools\php84\php.exe artisan queue:work
```

**Probar que todo funciona:**

1. Entra al relay (<http://127.0.0.1:8100>), agrega un número de proveedor y presiona **Sincronizar**.
2. En el historial del relay debe aparecer como correcta.
3. Entra al portal (<http://127.0.0.1:8000>) con el administrador. En **Sincronizaciones** aparece la corrida, y en el **Calendario** se ven las etiquetas y la instrucción de entrega del proveedor.

### 1.7 Pruebas automáticas

```powershell
cd C:\dev-ykm\YSP
C:\dev-ykm\tools\php84\php.exe vendor\bin\pest

cd C:\dev-ykm\supply-relay
.venv\Scripts\python.exe -m pytest
```

La prueba `ExampleTest` del portal viene de la plantilla y falla porque `/` redirige al inicio de sesión. Es normal.

---

## 2. En el servidor Windows con IIS (producción)

### 2.1 Dónde va cada uno

- **Portal:** en un servidor accesible para los proveedores, con **HTTPS** obligatorio.
- **Supply Relay:** en un servidor de la **red interna** que tenga acceso a Infor y salida hacia el portal. Su pantalla de administración **no** debe publicarse a internet.

Pueden estar en el mismo servidor o en servidores distintos; los pasos son los mismos.

### 2.2 Preparar el servidor (una sola vez)

1. **Roles de IIS** (PowerShell como administrador):
   ```powershell
   Install-WindowsFeature Web-Server, Web-CGI, Web-Mgmt-Console
   ```
   `Web-CGI` es el que habilita FastCGI para PHP.
2. Instala:
   - [Visual C++ Redistributable 2015-2022 x64](https://aka.ms/vs/17/release/vc_redist.x64.exe): lo necesita PHP.
   - [URL Rewrite](https://www.iis.net/downloads/microsoft/url-rewrite): para el portal.
   - [HttpPlatformHandler](https://www.iis.net/downloads/microsoft/httpplatformhandler): para el relay.
3. **Certificado HTTPS** para el dominio del portal, importado en IIS.
4. **Hora sincronizada** en ambos servidores (NTP). Si los relojes difieren más de 5 minutos, el portal rechaza las sincronizaciones.

### 2.3 Portal en IIS

**a) PHP 8.4**

1. Descomprime PHP 8.4 **NTS x64** en `C:\PHP\8.4` y crea el `php.ini` como en 1.2, agregando:
   ```ini
   zend_extension=opcache
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.validate_timestamps=0   ; más rápido; después de actualizar hay que reciclar el Application Pool
   fastcgi.impersonate = 1
   ```
2. Registra PHP en IIS como FastCGI con tiempos de **150 s**. El portal permite hasta 120 s por sincronización y el valor por defecto de IIS (70–90 s) cortaría a los proveedores grandes:
   ```powershell
   $appcmd = "$env:windir\system32\inetsrv\appcmd.exe"
   & $appcmd set config /section:system.webServer/fastCgi /+"[fullPath='C:\PHP\8.4\php-cgi.exe',activityTimeout='150',requestTimeout='150',instanceMaxRequests='10000']"
   & $appcmd set config /section:system.webServer/handlers /+"[name='PHP-FastCGI',path='*.php',verb='*',modules='FastCgiModule',scriptProcessor='C:\PHP\8.4\php-cgi.exe',resourceType='Either']"
   ```

**b) Archivos del proyecto**

1. Copia el proyecto a `C:\inetpub\portal`, **sin** `node_modules`, `.env`, `storage\logs\*` ni `tests`.
2. Compila los estilos **en tu computadora** (`npm run build`) y copia también la carpeta `public\build`. Así el servidor no necesita Node.js.
3. En el servidor:
   ```powershell
   cd C:\inetpub\portal
   C:\PHP\8.4\php.exe C:\ruta\a\composer.phar install --no-dev --optimize-autoloader
   ```

**c) `.env` de producción**

```ini
APP_ENV=production
APP_DEBUG=false                    ; nunca true en producción: mostraría datos internos en los errores
APP_URL=https://proveedores.tu-dominio.com
APP_KEY=                           ; php artisan key:generate (una sola vez)
APP_LOCALE=es
APP_TIMEZONE=America/Mexico_City
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=portal
DB_USERNAME=portal
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database

MAIL_MAILER=smtp                   ; para "Olvidé mi contraseña" y los avisos de inicio de sesión
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=portal@tu-dominio.com
MAIL_FROM_NAME="Portal de Proveedores"

SYNC_KEY=                          ; la misma que el relay de producción (NO la de tu computadora)
SYNC_ALLOWED_IPS=                  ; IP pública con la que sale el relay; si se llena, solo esa IP puede sincronizar
PORTAL_CALENDAR_FACILITY=YK1
PORTAL_CALENDAR_WORK_CENTER=
```

Después:

```powershell
cd C:\inetpub\portal
C:\PHP\8.4\php.exe artisan key:generate        # solo la primera vez
C:\PHP\8.4\php.exe artisan migrate --force
C:\PHP\8.4\php.exe artisan optimize             # guarda en caché configuración, rutas y vistas
C:\PHP\8.4\php.exe artisan geoip:update         # base para mostrar desde dónde se inició sesión
```

Crea el primer administrador con el mismo comando de `tinker` de la sección 1.4.

**d) Sitio en IIS**

1. **Application Pool** `Portal`: *No Managed Code*, modo *Integrated*.
2. **Sitio** `Portal`:
   - Ruta física: `C:\inetpub\portal\public`. Debe apuntar a **public**, nunca a la raíz del proyecto, para que nadie pueda abrir `.env`.
   - Enlace HTTPS (443) con el certificado.
   - Opcional: un enlace en el puerto 80 que redirija a HTTPS.
3. **Permisos de escritura** para el Application Pool:
   ```powershell
   icacls C:\inetpub\portal\storage /grant "IIS AppPool\Portal:(OI)(CI)M"
   icacls C:\inetpub\portal\bootstrap\cache /grant "IIS AppPool\Portal:(OI)(CI)M"
   ```
4. El `public\web.config` del proyecto ya trae:
   - Las reglas de URL Rewrite.
   - El límite de 50 MB por envío.
   - El bloqueo de `.env`.

**e) Tareas programadas del portal** (Programador de tareas de Windows)

| Tarea | Programa | Argumentos | Iniciar en | Cuándo |
|---|---|---|---|---|
| Correos en cola | `C:\PHP\8.4\php.exe` | `artisan queue:work --stop-when-empty --tries=3` | `C:\inetpub\portal` | Cada 5 minutos |
| Base de geolocalización | `C:\PHP\8.4\php.exe` | `artisan geoip:update` | `C:\inetpub\portal` | Una vez al mes |

Configura las dos para ejecutarse aunque el usuario no haya iniciado sesión.

### 2.4 Supply Relay en IIS

Los pasos están en el [README del relay](../../supply-relay/README.md#instalación-windows-server--iis). En resumen:

1. Python 3.12, el controlador **ODBC de IBM i Access de 64 bits** y HttpPlatformHandler.
2. Copia el proyecto a `C:\inetpub\supply-relay`, **sin** `.venv`, `.env`, `data` ni `logs`.
3. Crea el entorno virtual e instala las dependencias:
   ```powershell
   py -3.12 -m venv .venv
   .venv\Scripts\python.exe -m pip install -r requirements.txt
   ```
4. `.env` como en 1.5, con estos cambios para producción:
   ```ini
   PORTAL_URL=https://proveedores.tu-dominio.com
   PORTAL_VERIFY_TLS=true
   SECURE_COOKIES=true
   SYNC_KEY=          ; llave NUEVA de producción, igual en el portal
   SECRET_KEY=        ; llave NUEVA de producción
   ADMIN_PASSWORD_HASH=   ; contraseña NUEVA (no reutilices la de tu computadora)
   ```
5. En `web.config`, cambia `C:\inetpub\supply-relay` si usaste otra ruta.
6. Sitio en IIS:
   - Application Pool *No Managed Code*.
   - Enlace HTTPS **solo en la red interna**.
   - Permiso de escritura del Application Pool en `data` y `logs`:
     ```powershell
     icacls C:\inetpub\supply-relay\data /grant "IIS AppPool\SupplyRelay:(OI)(CI)M"
     icacls C:\inetpub\supply-relay\logs /grant "IIS AppPool\SupplyRelay:(OI)(CI)M"
     ```
7. Carga los proveedores iniciales: `.venv\Scripts\python.exe -m relay.seed`.
8. Programador de la sincronización automática, con una sola tarea del Programador de tareas. Los días y las horas se eligen después en la pestaña **Programación** del relay:

   | Programa | Argumentos | Iniciar en | Cuándo |
   |---|---|---|---|
   | `C:\inetpub\supply-relay\.venv\Scripts\python.exe` | `-m relay.scheduler` | `C:\inetpub\supply-relay` | Diario a las 00:00, repetir cada 1 minuto durante 1 día |

### 2.5 Verificar la instalación

- [ ] `https://proveedores.../signin` muestra el inicio de sesión. `https://proveedores.../.env` da error 404 o 403, **nunca** el archivo.
- [ ] El administrador entra, cambia de idioma y ve el menú de administración.
- [ ] En el relay interno, **Sincronizar** un proveedor termina como correcta.
- [ ] En el portal, **Sincronizaciones** muestra esa corrida, y el calendario las etiquetas y la instrucción de entrega.
- [ ] Un usuario proveedor descarga etiquetas y la instrucción de entrega, y aparece la marca de descargado.
- [ ] "Olvidé mi contraseña" envía el correo.
- [ ] En la pestaña **Programación** del relay, el programador aparece **en marcha**, y a la hora programada el historial muestra una corrida "Programada".

---

## 3. Actualizar a una versión nueva

**Portal**

```powershell
cd C:\inetpub\portal
C:\PHP\8.4\php.exe artisan down                 # muestra "en mantenimiento"
# copia los archivos nuevos (sin tocar .env ni storage) y la carpeta public\build compilada
C:\PHP\8.4\php.exe C:\ruta\a\composer.phar install --no-dev --optimize-autoloader
C:\PHP\8.4\php.exe artisan migrate --force
C:\PHP\8.4\php.exe artisan optimize
C:\PHP\8.4\php.exe artisan up
```

Después recicla el Application Pool `Portal`; con `opcache.validate_timestamps=0` es obligatorio para que tome el código nuevo.

**Supply Relay:** detén el sitio, copia los archivos nuevos (sin tocar `.env`, `data` ni `logs`), ejecuta `.venv\Scripts\python.exe -m pip install -r requirements.txt` e inicia el sitio.

---

## 4. Respaldos

| Qué | Cómo | Por qué |
|---|---|---|
| Base del portal | `pg_dump -Fc -U portal portal > portal.backup`, diario | Usuarios, descargas e historial. Los datos de Infor se pueden volver a sincronizar |
| `.env` del portal y del relay | Copia en un lugar seguro | Llaves; sin `APP_KEY` no se pueden leer las sesiones ni los datos cifrados |
| `supply-relay\data\relay.db` | Copia diaria | Lista de proveedores dados de alta e historial del relay |

---

## 5. Problemas comunes

| Síntoma | Causa | Solución |
|---|---|---|
| `composer install` dice que requiere PHP 8.4 | Estás usando otro PHP (por ejemplo, el de XAMPP 8.2) | Usa la ruta completa de PHP 8.4 |
| El relay dice `El portal respondió 401` | `SYNC_KEY` distinta en los dos `.env`, o los relojes difieren más de 5 minutos | Iguala las llaves; sincroniza la hora (NTP) |
| El relay dice `El portal respondió 503` | El portal no tiene `SYNC_KEY` o es muy corta | Pon en el portal la misma llave de 64 caracteres que en el relay |
| El relay dice `El portal respondió 403` | La IP del relay no está en `SYNC_ALLOWED_IPS` | Agrega la IP con la que sale el relay, o deja la variable vacía |
| El relay dice `El portal respondió 413` | El envío pasó el límite | Revisa `post_max_size` en `php.ini` (64M) y `maxAllowedContentLength` en `public\web.config` |
| El relay dice `500: Maximum execution time` o se corta a los 90 s | Tiempos de FastCGI | `activityTimeout` y `requestTimeout` en 150 (sección 2.3 a) |
| Error 500 en todo el portal | Permisos o `.env` | Revisa `storage\logs\laravel.log`; da permisos de escritura a `storage` y `bootstrap\cache` |
| Error 419 al iniciar sesión | La sesión expiró, o `SESSION_SECURE_COOKIE=true` sin HTTPS | Recarga la página; en local deja `SESSION_SECURE_COOKIE` vacío |
| Los estilos se ven rotos | Falta `public\build` | `npm run build` y copia la carpeta |
| No llegan los correos | No corre la cola o el SMTP está mal | Tarea `queue:work` (2.3 e); revisa `storage\logs\laravel.log` |
| El relay no conecta a Infor | Controlador ODBC de 32 bits, o usuario/contraseña | Instala el de 64 bits; prueba la conexión desde el Administrador de ODBC |
| Un cambio en el código no aparece en el servidor | OPcache | Recicla el Application Pool |
