# SNIES — Sistema Nacional de Información de la Educación Superior

Aplicación web para **consultar y administrar** Instituciones de Educación Superior (IES) de Colombia y sus directivos: búsqueda con filtros, listado público, y un CRUD protegido de directivos con registro de auditoría.

- **Backend:** PHP 8.5 (sin framework) + PDO
- **Base de datos:** PostgreSQL (alojada en **Supabase**)
- **Frontend:** Tailwind CSS + Font Awesome (servidos localmente desde `public/media/`)

---

## Estructura del proyecto

```
db_project/
├── connect.php            # Conexión PDO (lee credenciales desde .env vía config)
├── validation.php         # Validaciones de entrada (texto, email, número, id, password)
├── atributtes.php         # Contadores agregados para la página de búsqueda
├── .env                   # Configuración y credenciales (NO se versiona)
├── .env.example           # Plantilla de configuración
├── includes/              # Infraestructura compartida
│   ├── config.php         #   Parser de .env, entorno, constantes de BD
│   ├── bootstrap.php      #   Punto de entrada único (sesión + conexión + helpers)
│   ├── auth.php           #   require_login / require_role / CSRF
│   ├── render.php         #   Escape e() y render de filas
│   └── layout.php         #   render_head() / render_footer() (assets unificados)
└── public/
    ├── main/              # index.php (búsqueda), results.php, header.php
    ├── login/             # login, registro, logout y sus procesadores
    ├── crud/              # directivos, add/edit/delete, modificaciones, registros_autoria
    └── media/             # style.js (Tailwind), style.css (Font Awesome), imágenes
```

Cada página incluye `includes/bootstrap.php` como **primera instrucción**, lo que arranca la sesión, abre la conexión (`$conn`) y carga los helpers.

---

## Requisitos

- **PHP 8.x** con las extensiones `pdo_pgsql`, `pgsql` y `openssl`.
- Acceso a una base de datos **PostgreSQL** (este proyecto usa Supabase).

> En Windows, el binario de [windows.php.net](https://windows.php.net) trae esas extensiones **deshabilitadas**. Hay que crear un `php.ini` junto a `php.exe` con:
> ```ini
> extension_dir = "<ruta>/ext"
> extension=pdo_pgsql
> extension=pgsql
> extension=openssl
> ```

---

## Configuración (`.env`)

Copia `.env.example` a `.env` y completa los valores. Para Supabase se usa el **pooler**:

```ini
APP_ENV=development          # development | production

DB_HOST=aws-1-us-east-1.pooler.supabase.com
DB_PORT=5432                 # 5432 = session pooler · 6543 = transaction pooler
DB_NAME=postgres
DB_USER=postgres.<project-ref>
DB_PASS=                     # Reset en: Dashboard → Settings → Database → Reset password
DB_SSLMODE=require
```

`.env` está en `.gitignore`: **nunca se sube al repositorio**. `config.php` lee estas variables y, en `production`, oculta los errores al usuario (solo los registra con `error_log`).

---

## Cómo ejecutar (local)

Desde la raíz del proyecto, con el servidor embebido de PHP:

```bash
php -S localhost:8000 -t .
```

Luego abre en el navegador:

| Página | URL |
|--------|-----|
| Búsqueda de instituciones | http://localhost:8000/public/main/index.php |
| Iniciar sesión | http://localhost:8000/public/login/login.php |
| Panel (tras login) | http://localhost:8000/public/crud/modificaciones.php |

### Usuario de prueba (semilla)

```
correo:     jesusdel1611@gmail.com
contraseña: Admin#2026
rol:        admin
```

> ⚠️ Cámbialo antes de cualquier despliegue real.

---

## Base de datos

El esquema (13 tablas en `public` + esquema `privado`) se reconstruyó a partir de **`Instituciones.xlsx`** (hojas *Instituciones*, *Directivos*, *Cobertura*) con un proceso de limpieza determinista:

- Normalización de catálogos sucios (p. ej. `ACTO_ADMIN` 84 → 59 variantes unificando mayúsculas, acentos, encoding y puntos finales).
- Booleanos derivados: `SECTOR → publica`, `ESTADO → activa`, `PRINCIPAL_SECCIONAL → seccional`.
- Deduplicación de directivos por persona y de relaciones `rigen` por (directivo, cargo, IES padre).
- Generación de claves *surrogate* para departamentos/municipios (el Excel no trae códigos DANE).

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `instituciones` | IES padre (nombre, sector, carácter académico, NIT, misión…) |
| `inst_por_mun` | Cada sede/seccional (estado, página web, norma de creación…) |
| `cobertura` | Municipios cubiertos por cada institución |
| `departamentos`, `municipios` | Geografía |
| `caracter_academico`, `acto_administrativo`, `norma_creacion` | Catálogos de instituciones |
| `directivos`, `rigen`, `cargos`, `acto_nombr` | Directivos y sus cargos por institución |
| `privado.usuarios` | Cuentas de la app (login con bcrypt) |
| `privado.registro_autoria` | Auditoría de cambios |

---

## Seguridad

Cambios aplicados sobre la versión inicial:

- **SQL 100 % parametrizado** (sentencias preparadas; sin interpolación de entrada).
- **Control de acceso** (`require_login` / `require_role`) en las páginas del CRUD.
- **Protección CSRF** en formularios POST; el borrado solo se ejecuta por POST.
- **Credenciales fuera del código** (en `.env`, ignorado por git).
- **Errores no se filtran** al usuario en producción (`display_errors=0` + `error_log`).
- Contraseñas con `password_hash`/`password_verify` (bcrypt).

### Pendientes recomendados

- Activar **RLS** en las tablas de `public` de Supabase (hoy expuestas a la *anon key*).
- Rotar las credenciales que estuvieron en el historial de git.
- Compilar Tailwind a CSS estático para producción (hoy usa el Play CDN local).
