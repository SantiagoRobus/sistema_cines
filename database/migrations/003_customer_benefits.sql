DO $migration$
BEGIN
    ALTER TABLE public.TARIFA
        ADD COLUMN IF NOT EXISTS beneficio_requerido VARCHAR(30);

    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'ck_tarifa_beneficio_requerido'
          AND conrelid = 'public.tarifa'::regclass
    ) THEN
        ALTER TABLE public.TARIFA
            DROP CONSTRAINT ck_tarifa_beneficio_requerido;
    END IF;

    ALTER TABLE public.TARIFA
        ADD CONSTRAINT ck_tarifa_beneficio_requerido
        CHECK (beneficio_requerido IS NULL OR beneficio_requerido IN (
            'estudiante', 'pensionado', 'servicio_militar', 'discapacidad'
        ));
END
$migration$;

CREATE TABLE IF NOT EXISTS CLIENTE_BENEFICIO (
    cod_usuario INT PRIMARY KEY,
    tipo_beneficio VARCHAR(30) NOT NULL
        CHECK (tipo_beneficio IN ('estudiante', 'pensionado', 'servicio_militar', 'discapacidad')),
    estado VARCHAR(20) NOT NULL DEFAULT 'aprobado'
        CHECK (estado IN ('aprobado', 'vencido', 'revocado')),
    metodo_validacion VARCHAR(20) NOT NULL
        CHECK (metodo_validacion IN ('documental', 'presencial')),
    vigente_hasta DATE NOT NULL,
    validado_por INT NOT NULL,
    actualizado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cliente_beneficio_cliente
        FOREIGN KEY (cod_usuario) REFERENCES CLIENTE(cod_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_cliente_beneficio_validador
        FOREIGN KEY (validado_por) REFERENCES USUARIO(cod_usuario)
);

CREATE TABLE IF NOT EXISTS SOLICITUD_BENEFICIO (
    cod_solicitud SERIAL PRIMARY KEY,
    cod_usuario INT NOT NULL,
    tipo_beneficio VARCHAR(30) NOT NULL
        CHECK (tipo_beneficio IN ('estudiante', 'pensionado', 'servicio_militar', 'discapacidad')),
    archivo_clave VARCHAR(80),
    archivo_tipo VARCHAR(80),
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
        CHECK (estado IN ('pendiente', 'aprobado', 'rechazado')),
    motivo_revision VARCHAR(300),
    creado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_en TIMESTAMPTZ,
    revisado_por INT,
    CONSTRAINT fk_solicitud_beneficio_cliente
        FOREIGN KEY (cod_usuario) REFERENCES CLIENTE(cod_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_beneficio_revisor
        FOREIGN KEY (revisado_por) REFERENCES USUARIO(cod_usuario)
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_solicitud_beneficio_pendiente
    ON SOLICITUD_BENEFICIO (cod_usuario)
    WHERE estado = 'pendiente';

CREATE INDEX IF NOT EXISTS idx_solicitud_beneficio_estado_fecha
    ON SOLICITUD_BENEFICIO (estado, creado_en);

CREATE TABLE IF NOT EXISTS NOTIFICACION_ADMIN (
    cod_notificacion SERIAL PRIMARY KEY,
    cod_solicitud INT NOT NULL UNIQUE,
    creada_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leida_en TIMESTAMPTZ,
    CONSTRAINT fk_notificacion_solicitud
        FOREIGN KEY (cod_solicitud) REFERENCES SOLICITUD_BENEFICIO(cod_solicitud) ON DELETE CASCADE
);
