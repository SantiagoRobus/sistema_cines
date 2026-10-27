# 🎬 Sistema de Gestión de Cines - Cinex+ (Entrega 1)

Bienvenido al repositorio del **Sistema de Gestión de Cines**. Este proyecto es una aplicación web desarrollada con arquitectura modular en PHP puro y PostgreSQL como motor de base de datos relacional.

---

## 📌 Propósito y Alcance de esta Versión

Esta primera entrega académica cubre el flujo completo de autenticación por roles, seguridad de contraseñas, gestión centralizada mediante interfaces de una sola página (*Single Page Application* con modales y AJAX/SweetAlert2) y control de acceso según perfil de usuario.

### 🏛️ Módulos Implementados

1. **Autenticación y Seguridad:**
   - **Inicio de sesión (`login.php`):** Control de acceso por roles (Administrador, Empleado, Cliente). Encriptación de contraseñas mediante `password_hash()` con algoritmo Bcrypt.
   - **Registro de usuarios (`registro.php`):** Registro público por defecto con rol Cliente (Rol 3) y asignación dinámica de rol Empleado (Rol 2) o Admin (Rol 1) mediante códigos clave.
   - **Recuperación de contraseña (`recuperar_password.php`):** Verificación de correo en base de datos y actualización segura del hash.

2. **Panel Administrativo (`dashboard_admin.php`):**
   - Interfaz gráfica moderna con tarjetas de acceso rápido (*Cards Grid*).
   - Control estricto de sesión y sanitización de parámetros en la URL contra manipulación de ID.

3. **Gestión CRUD en Una Sola Página (Modales + SweetAlert2):**
   - **CRUD de Usuarios:** Alta, edición y eliminación (con regla de negocio que impide al Administrador autoborrarse).
   - **CRUD de Catálogos (`crud_catalogos.php`):** Control unificado para las tablas `GENERO` y `TARIFA`.
   - **CRUD de Sucursales (`crud_cines.php`):** Registro de sedes físicas.
   - **CRUD de Salas (`crud_salas.php`):** Relación de salas por cine mediante menús desplegables (`<select>`) vinculados a PostgreSQL.
   - **CRUD de Películas (`crud_peliculas.php`):** Gestión de catálogo de películas asociadas a géneros.

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
│   └── conexion.php        # Conexión PDO a PostgreSQL
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

Lenguaje: PHP 8.x con la extensión pdo_pgsql activa.

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

Configurar tus credenciales de PostgreSQL en el archivo config/conexion.php.

Abrir en el navegador: http://localhost/sistema_cines/views/public/login.php.