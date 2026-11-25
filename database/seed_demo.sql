-- SOLO PARA DESARROLLO LOCAL. No ejecutes este archivo en producción.
-- Cuentas de prueba:
-- admin@cinex.local   / CineAdmin!2025
-- empleado@cinex.local / Empleado!2025
-- cliente@cinex.local / Cliente!2025
--
-- Ejecutar después del esquema base y de las migraciones 001, 002, 003 y 004.

BEGIN;

INSERT INTO ROL (cod_rol, nombre) VALUES
    (1, 'Administrador'),
    (2, 'Empleado'),
    (3, 'Cliente')
ON CONFLICT (cod_rol) DO NOTHING;

SELECT setval(
    pg_get_serial_sequence('rol', 'cod_rol'),
    GREATEST(COALESCE((SELECT MAX(cod_rol) FROM ROL), 1), 1),
    true
);

INSERT INTO CINE (nombre, calle, numero, telefono, activo)
SELECT 'Cinex Centro Demo', 'Carrera 10', '20-30', '6015550100', '1'
WHERE NOT EXISTS (SELECT 1 FROM CINE WHERE nombre = 'Cinex Centro Demo');

INSERT INTO SALA (num_sala, capacidad, cod_cine)
SELECT 1, 80, c.cod_cine
FROM CINE c
WHERE c.nombre = 'Cinex Centro Demo'
  AND NOT EXISTS (
      SELECT 1 FROM SALA s
      WHERE s.cod_cine = c.cod_cine AND s.num_sala = 1
  );

INSERT INTO GENERO (nombre)
SELECT datos.nombre
FROM (VALUES ('Aventura'), ('Comedia'), ('Ciencia ficción')) AS datos(nombre)
WHERE NOT EXISTS (SELECT 1 FROM GENERO g WHERE g.nombre = datos.nombre);

INSERT INTO CLASIFICACION (descripcion)
SELECT 'Todo público'
WHERE NOT EXISTS (SELECT 1 FROM CLASIFICACION WHERE descripcion = 'Todo público');

INSERT INTO DIRECTOR (nombre)
SELECT 'Dirección Demo'
WHERE NOT EXISTS (SELECT 1 FROM DIRECTOR WHERE nombre = 'Dirección Demo');

INSERT INTO PELICULA (titulo, cod_director, cod_genero, cod_clasif, duracion_min, sinopsis)
SELECT datos.titulo, d.cod_director, g.cod_genero, cl.cod_clasif, datos.duracion, datos.sinopsis
FROM (VALUES
    ('La aventura del faro', 'Aventura', 110, 'Una familia descubre un misterio en un antiguo faro.'),
    ('Risas de domingo', 'Comedia', 95, 'Dos amigos intentan organizar el festival del barrio.'),
    ('Órbita cercana', 'Ciencia ficción', 120, 'Una misión espacial recibe una señal inesperada.')
) AS datos(titulo, genero, duracion, sinopsis)
JOIN DIRECTOR d ON d.nombre = 'Dirección Demo'
JOIN GENERO g ON g.nombre = datos.genero
JOIN CLASIFICACION cl ON cl.descripcion = 'Todo público'
WHERE NOT EXISTS (SELECT 1 FROM PELICULA p WHERE p.titulo = datos.titulo);

INSERT INTO TARIFA (descripcion, beneficio_requerido)
SELECT datos.descripcion, datos.beneficio
FROM (VALUES
    ('General Demo', NULL::VARCHAR(30)),
    ('Estudiante Demo', 'estudiante'::VARCHAR(30)),
    ('Pensionado Demo', 'pensionado'::VARCHAR(30)),
    ('Servicio militar Demo', 'servicio_militar'::VARCHAR(30)),
    ('Discapacidad Demo', 'discapacidad'::VARCHAR(30))
) AS datos(descripcion, beneficio)
WHERE NOT EXISTS (SELECT 1 FROM TARIFA t WHERE t.descripcion = datos.descripcion);

INSERT INTO USUARIO (nombre, apellidos, email, password_hash, telefono, cod_rol, activo)
VALUES
    ('Admin', 'Demo', 'admin@cinex.local', '$2y$10$XMq2igav.rgnymfxyXUQdeH4gH0RzycEOU.kftJ0P/EHSUqsyzKgq', '3005550101', 1, '1'),
    ('Empleado', 'Demo', 'empleado@cinex.local', '$2y$10$QJdA65sCDFZGpyn6mWxsqudpDbmVmFe5qhyb658STFsuBLgpkmgHO', '3005550102', 2, '1'),
    ('Cliente', 'Demo', 'cliente@cinex.local', '$2y$10$4l.RDbKU91lvG1eo8ohUzezLGEKUESJPlkI2HR3SLoITMy/Q2M4jC', '3005550103', 3, '1')
ON CONFLICT (email) DO NOTHING;

INSERT INTO ADMIN_LOCAL (cod_usuario, cod_cine)
SELECT u.cod_usuario, c.cod_cine
FROM USUARIO u CROSS JOIN CINE c
WHERE u.email = 'admin@cinex.local' AND c.nombre = 'Cinex Centro Demo'
ON CONFLICT (cod_usuario) DO NOTHING;

INSERT INTO EMPLEADO (cod_usuario, cod_cine, cargo, fecha_alta)
SELECT u.cod_usuario, c.cod_cine, 'Taquilla', CURRENT_DATE
FROM USUARIO u CROSS JOIN CINE c
WHERE u.email = 'empleado@cinex.local' AND c.nombre = 'Cinex Centro Demo'
ON CONFLICT (cod_usuario) DO NOTHING;

INSERT INTO CLIENTE (cod_usuario)
SELECT cod_usuario FROM USUARIO WHERE email = 'cliente@cinex.local'
ON CONFLICT (cod_usuario) DO NOTHING;

INSERT INTO PRECIO (cod_cine, cod_tarifa, importe, dias_aplicacion)
SELECT c.cod_cine, t.cod_tarifa,
       CASE t.descripcion
           WHEN 'General Demo' THEN 18000
           WHEN 'Estudiante Demo' THEN 12000
           WHEN 'Pensionado Demo' THEN 12000
           WHEN 'Servicio militar Demo' THEN 12000
           WHEN 'Discapacidad Demo' THEN 12000
       END,
       'Todos'
FROM CINE c CROSS JOIN TARIFA t
WHERE c.nombre = 'Cinex Centro Demo'
  AND t.descripcion IN (
      'General Demo', 'Estudiante Demo', 'Pensionado Demo',
      'Servicio militar Demo', 'Discapacidad Demo'
  )
ON CONFLICT (cod_cine, cod_tarifa) DO UPDATE
SET importe = EXCLUDED.importe,
    dias_aplicacion = EXCLUDED.dias_aplicacion;

-- Horarios de muestra para los próximos días.
INSERT INTO PASE (cod_cine, num_sala, cod_pelicula, fecha, hora, matinal)
SELECT c.cod_cine, 1, p.cod_pelicula, CURRENT_DATE + horarios.dias, horarios.hora, horarios.matinal
FROM CINE c
CROSS JOIN (VALUES
    (1, TIME '14:00', 'S'),
    (1, TIME '18:30', 'N'),
    (2, TIME '16:00', 'N'),
    (2, TIME '20:30', 'N')
) AS horarios(dias, hora, matinal)
JOIN PELICULA p ON p.titulo = CASE
    WHEN horarios.hora = TIME '14:00' THEN 'La aventura del faro'
    WHEN horarios.hora = TIME '18:30' THEN 'Risas de domingo'
    WHEN horarios.hora = TIME '16:00' THEN 'Órbita cercana'
    ELSE 'La aventura del faro'
END
WHERE c.nombre = 'Cinex Centro Demo'
  AND NOT EXISTS (
      SELECT 1 FROM PASE existente
      WHERE existente.cod_cine = c.cod_cine
        AND existente.num_sala = 1
        AND existente.cod_pelicula = p.cod_pelicula
        AND existente.fecha = CURRENT_DATE + horarios.dias
        AND existente.hora = horarios.hora
  );

-- Beneficio de ejemplo para probar la tarifa de estudiante.
INSERT INTO CLIENTE_BENEFICIO
    (cod_usuario, tipo_beneficio, estado, metodo_validacion, vigente_hasta, validado_por)
SELECT cliente.cod_usuario, 'estudiante', 'aprobado', 'documental',
       CURRENT_DATE + INTERVAL '1 year', admin.cod_usuario
FROM USUARIO cliente
CROSS JOIN USUARIO admin
WHERE cliente.email = 'cliente@cinex.local'
  AND admin.email = 'admin@cinex.local'
ON CONFLICT (cod_usuario) DO NOTHING;

-- Reserva de taquilla de ejemplo para mostrar ocupación y reservas al empleado.
INSERT INTO RESERVA
    (cod_cliente, cod_pase, cod_tarifa, cod_empleado, num_entradas, precio_total, estado)
SELECT cliente.cod_usuario, p.cod_pase, t.cod_tarifa, empleado.cod_usuario, 2, 36000, 'Confirmada'
FROM USUARIO cliente
CROSS JOIN USUARIO empleado
JOIN CINE c ON c.nombre = 'Cinex Centro Demo'
JOIN PASE p ON p.cod_cine = c.cod_cine
JOIN PELICULA pelicula ON pelicula.cod_pelicula = p.cod_pelicula
JOIN TARIFA t ON t.descripcion = 'General Demo'
WHERE cliente.email = 'cliente@cinex.local'
  AND empleado.email = 'empleado@cinex.local'
  AND p.fecha = CURRENT_DATE + 1
  AND pelicula.titulo = 'La aventura del faro'
  AND p.hora = TIME '14:00'
  AND NOT EXISTS (
      SELECT 1 FROM RESERVA r
      WHERE r.cod_cliente = cliente.cod_usuario AND r.cod_pase = p.cod_pase
  );

COMMIT;

-- Verifica que las cuentas y funciones de prueba queden disponibles.
SELECT u.email, r.nombre AS rol
FROM USUARIO u
JOIN ROL r ON r.cod_rol = u.cod_rol
WHERE u.email IN ('admin@cinex.local', 'empleado@cinex.local', 'cliente@cinex.local')
ORDER BY r.cod_rol;

SELECT c.nombre AS cine, pelicula.titulo, p.fecha, p.hora, s.capacidad
FROM PASE p
JOIN CINE c ON c.cod_cine = p.cod_cine
JOIN PELICULA pelicula ON pelicula.cod_pelicula = p.cod_pelicula
JOIN SALA s ON s.cod_cine = p.cod_cine AND s.num_sala = p.num_sala
WHERE c.nombre = 'Cinex Centro Demo'
ORDER BY p.fecha, p.hora;
