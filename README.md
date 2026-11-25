# 🎬 Sistema de Gestión de Cines - Cinex+ (Entrega 1)

Bienvenido al repositorio del **Sistema de Gestión de Cines**. Este proyecto es una aplicación web desarrollada con arquitectura modular en PHP puro y PostgreSQL como motor de base de datos relacional.

---

## 📌 Propósito y Alcance de esta Versión

Esta primera entrega académica cubre el flujo completo de autenticación por roles, seguridad de contraseñas, gestión centralizada mediante interfaces de una sola página (*Single Page Application* con modales y AJAX/SweetAlert2) y control de acceso según perfil de usuario.

### 🏛️ Módulos Implementados

1. **Autenticación y Seguridad:**
   - **Inicio de sesión (`login.php`):** Control de acceso por roles (Administrador, Empleado, Cliente). Encriptación de contraseñas mediante `password_hash()` con algoritmo Bcrypt.
   - **Registro de usuarios (`registro.php`):** El registro público crea únicamente cuentas Cliente; los roles internos se asignan desde administración.
   - **Recuperación de contraseña (`recuperar_password.php`):** Enlaces temporales de un solo uso, almacenados como hash y válidos durante 30 minutos.
   - **Protección de formularios:** Sesiones reforzadas, regeneración del identificador al iniciar sesión y tokens CSRF en las operaciones de escritura.

2. **Panel Administrativo (`dashboard_admin.php`):**
   - Interfaz gráfica moderna con tarjetas de acceso rápido (*Cards Grid*).
   - Control estricto de sesión y sanitización de parámetros en la URL contra manipulación de ID.

3. **Gestión CRUD en Una Sola Página (Modales + SweetAlert2):**
   - **CRUD de Usuarios:** Alta y edición de usuarios y perfiles según rol.
   - **CRUD de Catálogos (`crud_catalogos.php`):** Control de géneros, tarifas, clasificaciones, directores y actores.
   - **CRUD de Sucursales (`crud_cines.php`):** Registro de sedes físicas.
   - **CRUD de Salas (`crud_salas.php`):** Relación de salas por cine mediante menús desplegables (`<select>`) vinculados a PostgreSQL.
   - **Reportes (`reportes.php`):** Resumen de reservas, entradas y recaudo, con detalle o top 10 de películas y filtros por periodo, estado, cliente, correo, película y cine.
   - Los CRUD administrativos permiten crear y editar; no incluyen la acción de eliminar.
   - **CRUD de Películas (`crud_peliculas.php`):** Gestión de películas, género, director, clasificación y reparto.
   - **Programación (`crud_pases.php`):** Administración de funciones por cine, sala, película, fecha y hora.
   - **Precios (`crud_precios.php`):** Configuración de tarifas por sede en pesos colombianos.
   - **Cartelera y reservas:** Selección de cine, información de contacto, búsqueda de películas, filtros de fecha, disponibilidad y pago confirmado desde el servidor mediante Mercado Pago Checkout Pro.
   - **Beneficios:** El cliente puede solicitar una tarifa especial con soporte privado; el administrador aprueba/rechaza y recibe aviso en el panel y por correo cuando `mail()` está configurado. Un empleado también puede verificar el documento original en taquilla.
   - **Panel de empleado:** Ocupación por entradas, venta en taquilla, cancelación de ventas no pagadas, validación presencial de beneficios y actualización de perfil.
   - **Recuperación sin correo:** Si el servidor de correo no está configurado, la solicitud queda en el panel del administrador, quien puede verificar al cliente y generar un enlace de un solo uso.

---

## 📁 Estructura del Proyecto

```text
sistema_cines/
├── auth/                   # Lógica backend para procesamiento de datos
│   ├── create_user.php     # Procesa inserción de usuarios
│   ├── delete_user.php     # Sanitiza y elimina usuarios
│   ├── login_process.php   # Control de sesión y hashing
│   ├── procesar_catalogos.php
│   ├── procesar_cines.php
│   ├── procesar_peliculas.php
│   ├── procesar_salas.php
│   ├── register_process.php
│   ├── reset_password_process.php
│   └── update_user.php     # Procesa actualización de usuarios
├── config/                 # Configuración del sistema
│   ├── conexion.php        # Conexión PDO a PostgreSQL mediante variables de entorno
│   ├── mercadopago.php     # Cliente HTTP de Mercado Pago
│   ├── pagos.php           # Confirmación de pagos y reservas
│   └── seguridad.php       # Sesiones, roles y protección CSRF
├── database/migrations/    # Migraciones para recuperación, pagos y beneficios
├── views/                  # Vistas de la aplicación (Frontend)
│   ├── admin/              # Vistas protegidas para Rol 1 (Admin)
│   │   ├── crud_catalogos.php
│   │   ├── crud_cines.php
│   │   ├── crud_peliculas.php
│   │   ├── crud_salas.php
│   │   └── dashboard_admin.php
│   ├── cliente/            # Vistas protegidas para Rol 3 (Cliente)
│   │   └── dashboard_cliente.php
│   ├── empleado/           # Vistas protegidas para Rol 2 (Empleado)
│   │   └── dashboard_empleado.php
│   └── public/             # Vistas públicas de autenticación
│       ├── login.php
│       ├── recuperar_password.php
│       └── registro.php
├── .gitignore              # Archivos excluidos del repositorio Git
└── README.md               # Documentación del proyecto

🛠️ Requisitos del Entorno Local
Servidor Web: Apache (XAMPP).

Lenguaje: PHP 8.x con las extensiones `pdo_pgsql`, `curl` y `openssl` activas.

Base de Datos: PostgreSQL 12+ (Gestionado desde pgAdmin4).

Librerías Frontend Integradas (vía CDN):

SweetAlert2 (Para notificaciones y confirmaciones de eliminación).

IMPORTANTE!! Clonar el proyecto en tu carpeta de XAMPP (htdocs/):

git clone [https://github.com/SantiagoRobus/sistema_cines.git](https://github.com/SantiagoRobus/sistema_cines.git)

Importar el Script SQL en pgAdmin4 para crear las tablas en PostgreSQL.

-- 1. TABLAS INDEPENDIENTES (Sin llaves foráneas)
CREATE TABLE ROL (
    cod_rol SERIAL PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL
);

INSERT INTO ROL (cod_rol, nombre) VALUES
    (1, 'Administrador'),
    (2, 'Empleado'),
    (3, 'Cliente')
ON CONFLICT (cod_rol) DO NOTHING;

CREATE TABLE LOG_ACTIVIDAD (
    cod_log SERIAL PRIMARY KEY,
    accion VARCHAR(20),
    tabla_afectada VARCHAR(20),
    detalle VARCHAR(255),
    fecha_hora TIMESTAMP
);

CREATE TABLE CINE (
    cod_cine SERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    calle VARCHAR(100) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    telefono VARCHAR(15),
    activo CHAR(1) DEFAULT '1'
);

CREATE TABLE GENERO (
    cod_genero SERIAL PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL
);

CREATE TABLE CLASIFICACION (
    cod_clasif SERIAL PRIMARY KEY,
    descripcion VARCHAR(80) NOT NULL
);

CREATE TABLE DIRECTOR (
    cod_director SERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL
);

CREATE TABLE ACTOR (
    cod_actor SERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL
);

CREATE TABLE TARIFA (
    cod_tarifa SERIAL PRIMARY KEY,
    descripcion VARCHAR(60) NOT NULL
);

-- 2. TABLAS CON DEPENDENCIAS DE PRIMER NIVEL
CREATE TABLE SALA (
    cod_sala SERIAL PRIMARY KEY,
    num_sala INT NOT NULL,
    capacidad INT NOT NULL,
    cod_cine INT NOT NULL,
    CONSTRAINT fk_sala_cine FOREIGN KEY (cod_cine) REFERENCES CINE(cod_cine) ON DELETE CASCADE
);

CREATE TABLE USUARIO (
    cod_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(80) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(15),
    cod_rol INT NOT NULL,
    activo CHAR(1) DEFAULT '1',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cod_log INT,
    CONSTRAINT fk_usuario_rol FOREIGN KEY (cod_rol) REFERENCES ROL(cod_rol),
    CONSTRAINT fk_usuario_log FOREIGN KEY (cod_log) REFERENCES LOG_ACTIVIDAD(cod_log)
);

CREATE TABLE PELICULA (
    cod_pelicula SERIAL PRIMARY KEY,
    titulo VARCHAR(120) NOT NULL,
    cod_director INT,
    cod_genero INT,
    cod_clasif INT,
    duracion_min INT,
    sinopsis VARCHAR(500),
    CONSTRAINT fk_pelicula_director FOREIGN KEY (cod_director) REFERENCES DIRECTOR(cod_director),
    CONSTRAINT fk_pelicula_genero FOREIGN KEY (cod_genero) REFERENCES GENERO(cod_genero),
    CONSTRAINT fk_pelicula_clasif FOREIGN KEY (cod_clasif) REFERENCES CLASIFICACION(cod_clasif)
);

CREATE TABLE PRECIO (
    cod_cine INT NOT NULL,
    cod_tarifa INT NOT NULL,
    importe DECIMAL(6,2) NOT NULL,
    dias_aplicacion VARCHAR(40),
    PRIMARY KEY (cod_cine, cod_tarifa),
    CONSTRAINT fk_precio_cine FOREIGN KEY (cod_cine) REFERENCES CINE(cod_cine) ON DELETE CASCADE,
    CONSTRAINT fk_precio_tarifa FOREIGN KEY (cod_tarifa) REFERENCES TARIFA(cod_tarifa) ON DELETE CASCADE
);

-- 3. TABLAS DE PERFILES (Dependen de USUARIO)
CREATE TABLE CLIENTE (
    cod_usuario INT PRIMARY KEY,
    fecha_nacimiento DATE,
    carnet_estudiante CHAR(1),
    es_jubilado CHAR(1),
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (cod_usuario) REFERENCES USUARIO(cod_usuario) ON DELETE CASCADE
);

CREATE TABLE EMPLEADO (
    cod_usuario INT PRIMARY KEY,
    cod_cine INT NOT NULL,
    cargo VARCHAR(40),
    fecha_alta DATE,
    CONSTRAINT fk_empleado_usuario FOREIGN KEY (cod_usuario) REFERENCES USUARIO(cod_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_empleado_cine FOREIGN KEY (cod_cine) REFERENCES CINE(cod_cine)
);

CREATE TABLE ADMIN_LOCAL (
    cod_usuario INT PRIMARY KEY,
    cod_cine INT NOT NULL,
    CONSTRAINT fk_admin_usuario FOREIGN KEY (cod_usuario) REFERENCES USUARIO(cod_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_admin_cine FOREIGN KEY (cod_cine) REFERENCES CINE(cod_cine)
);

-- 4. TABLAS TRANSACCIONALES Y DE ROMPIMIENTO
CREATE TABLE PROTAGONIZA (
    cod_pelicula INT NOT NULL,
    cod_actor INT NOT NULL,
    orden INT,
    PRIMARY KEY (cod_pelicula, cod_actor),
    CONSTRAINT fk_protagoniza_pelicula FOREIGN KEY (cod_pelicula) REFERENCES PELICULA(cod_pelicula) ON DELETE CASCADE,
    CONSTRAINT fk_protagoniza_actor FOREIGN KEY (cod_actor) REFERENCES ACTOR(cod_actor) ON DELETE CASCADE
);

CREATE TABLE PASE (
    cod_pase SERIAL PRIMARY KEY,
    cod_cine INT NOT NULL,
    num_sala INT NOT NULL,
    cod_pelicula INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    matinal CHAR(1),
    CONSTRAINT fk_pase_pelicula FOREIGN KEY (cod_pelicula) REFERENCES PELICULA(cod_pelicula),
    CONSTRAINT fk_pase_cine FOREIGN KEY (cod_cine) REFERENCES CINE(cod_cine)
);

CREATE TABLE RESERVA (
    cod_reserva SERIAL PRIMARY KEY,
    cod_cliente INT NOT NULL,
    cod_pase INT NOT NULL,
    cod_tarifa INT NOT NULL,
    cod_empleado INT,
    num_entradas INT NOT NULL,
    precio_total DECIMAL(8,2) NOT NULL,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(12),
    CONSTRAINT fk_reserva_cliente FOREIGN KEY (cod_cliente) REFERENCES CLIENTE(cod_usuario),
    CONSTRAINT fk_reserva_pase FOREIGN KEY (cod_pase) REFERENCES PASE(cod_pase),
    CONSTRAINT fk_reserva_tarifa FOREIGN KEY (cod_tarifa) REFERENCES TARIFA(cod_tarifa),
    CONSTRAINT fk_reserva_empleado FOREIGN KEY (cod_empleado) REFERENCES EMPLEADO(cod_usuario)
);

Configurar las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` para Apache/PHP. Por defecto, la aplicación usa `127.0.0.1`, el puerto `5453`, la base de datos `sistema_cines` y el usuario `postgres`; define `DB_PASSWORD` con la contraseña de tu usuario de PostgreSQL. Nunca guardes contraseñas reales en el código.

Para iniciar el servidor integrado de PHP desde PowerShell, define las variables antes de ejecutar el comando:

```powershell
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '5453'
$env:DB_NAME = 'sistema_cines'
$env:DB_USER = 'postgres'
$env:DB_PASSWORD = 'TU_CLAVE_DE_POSTGRES'
$env:APP_BASE_URL = 'https://TU_URL_PUBLICA'
$env:MP_ACCESS_TOKEN = 'TEST-TU_ACCESS_TOKEN'
$env:MP_WEBHOOK_SECRET = 'TU_SECRETO_WEBHOOK'
$env:MP_MODE = 'sandbox'
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t .
```

Para desarrollo sin túnel público, `APP_BASE_URL` puede ser `http://localhost:8000`, pero las notificaciones de pago no llegarán hasta exponer el servidor por HTTPS.

Después de crear las tablas base, ejecuta en pgAdmin, en orden:

1. `database/migrations/001_password_reset_tokens.sql`
2. `database/migrations/002_reservations_mercadopago.sql`
3. `database/migrations/003_customer_benefits.sql`
4. `database/migrations/004_password_recovery_fallback.sql`

Para poblar una base local con cine, películas, horarios, precios y cuentas de demostración, ejecuta `database/seed_demo.sql` después de las migraciones. Las cuentas son `admin@cinex.local` / `CineAdmin!2025`, `empleado@cinex.local` / `Empleado!2025` y `cliente@cinex.local` / `Cliente!2025`. El seed es idempotente para sus datos identificados y no sobrescribe cuentas existentes con esos correos. **No lo ejecutes en producción.**

En pgAdmin abre cada archivo de migración, selecciona **todo el contenido** y ejecútalo de principio a fin; no ejecutes solamente las líneas de `ADD CONSTRAINT`. La migración 003 crea primero la columna de requisito de beneficio y después agrega su restricción.

Configura `APP_BASE_URL` con la URL pública de la aplicación y `MAIL_FROM` con un correo válido. PHP debe tener configurado el envío de correo (`mail()`); si no está disponible, los clientes aún pueden solicitar recuperación y administración les entrega un enlace temporal después de verificar su identidad.

Si `mail()` no funciona, el usuario puede solicitar el restablecimiento igualmente; administración verá la solicitud en **Recuperar cuentas**, verificará la identidad y generará un enlace válido por 30 minutos. En desarrollo local puedes dejar `APP_BASE_URL` sin configurar para este flujo manual; el enlace se generará para `http://127.0.0.1:8000`.

El registro público solo crea cuentas de cliente. Los roles de empleado y administrador se asignan desde el panel administrativo. Las contraseñas nuevas requieren al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo de esta lista: `* ? ! @ # $ / ( ) { } = . , ; :`. No se permiten espacios, patrones comunes/secuenciales ni el nombre, apellido o correo del usuario.

Para activar descuentos, crea/edita una tarifa en **Catálogos** y asígnale el requisito correspondiente; luego configura el precio de esa tarifa en cada cine. Solo clientes con acreditación aprobada y vigente podrán usarla. Los soportes se guardan fuera del directorio público en `cinex-private/beneficios` (o en la ruta externa configurada con `PRIVATE_STORAGE_PATH`) y se eliminan al terminar la revisión. Para correo de notificación de beneficios, configura `MAIL_FROM` y el transporte `mail()` de PHP.

El panel de empleado muestra la ocupación por cantidad de entradas y gestiona ventas de taquilla; la base actual no modela asientos individuales ni permite escoger una silla concreta.

### Pagos de prueba en Colombia

La integración utiliza **Mercado Pago Checkout Pro** y cobra en COP. Crea una aplicación en Mercado Pago Developers, configura sus credenciales de prueba como `MP_ACCESS_TOKEN`, usa `MP_MODE=sandbox` y configura `MP_WEBHOOK_SECRET`. No guardes estas credenciales en el repositorio. `APP_BASE_URL` debe apuntar a una URL HTTPS públicamente accesible para que Mercado Pago pueda enviar webhooks; durante desarrollo local usa un túnel HTTPS. Los pagos solo se confirman después de consultar la API de Mercado Pago, no por los parámetros de retorno del navegador.

Las reservas pendientes vencen a los 20 minutos para liberar capacidad. Si el proveedor reporta un pago aprobado después del vencimiento o sin capacidad disponible, la reserva se cancela y el pago queda marcado para revisión; el comercio debe gestionar el reembolso manualmente.

Abrir en el navegador: http://localhost/sistema_cines/views/public/login.php.