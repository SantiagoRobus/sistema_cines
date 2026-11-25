CREATE TABLE IF NOT EXISTS PASSWORD_RESET_REQUEST (
    cod_solicitud SERIAL PRIMARY KEY,
    cod_usuario INT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
        CHECK (estado IN ('pendiente', 'enlace_enviado', 'resuelto')),
    creado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendido_en TIMESTAMPTZ,
    atendido_por INT,
    CONSTRAINT fk_reset_request_usuario
        FOREIGN KEY (cod_usuario) REFERENCES USUARIO(cod_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_reset_request_admin
        FOREIGN KEY (atendido_por) REFERENCES USUARIO(cod_usuario)
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_reset_request_pendiente
    ON PASSWORD_RESET_REQUEST (cod_usuario)
    WHERE estado = 'pendiente';

CREATE INDEX IF NOT EXISTS idx_reset_request_estado_fecha
    ON PASSWORD_RESET_REQUEST (estado, creado_en);
