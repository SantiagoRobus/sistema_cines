ALTER TABLE PRECIO
    ALTER COLUMN importe TYPE NUMERIC(12, 2);

ALTER TABLE RESERVA
    ALTER COLUMN precio_total TYPE NUMERIC(14, 2);

CREATE TABLE PAGO_RESERVA (
    cod_pago SERIAL PRIMARY KEY,
    cod_reserva INT NOT NULL UNIQUE,
    referencia_externa VARCHAR(36) NOT NULL UNIQUE,
    preferencia_id VARCHAR(100) UNIQUE,
    checkout_url TEXT,
    pago_proveedor_id VARCHAR(100) UNIQUE,
    importe NUMERIC(14, 2) NOT NULL CHECK (importe > 0),
    moneda CHAR(3) NOT NULL DEFAULT 'COP',
    estado VARCHAR(30) NOT NULL DEFAULT 'creando',
    creado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pago_reserva
        FOREIGN KEY (cod_reserva) REFERENCES RESERVA(cod_reserva) ON DELETE CASCADE
);

CREATE INDEX idx_reserva_pase_estado_fecha
    ON RESERVA (cod_pase, estado, fecha_reserva);
