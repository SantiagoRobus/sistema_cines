CREATE TABLE PASSWORD_RESET_TOKEN (
    token_hash CHAR(64) PRIMARY KEY,
    cod_usuario INT NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    used_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_usuario
        FOREIGN KEY (cod_usuario) REFERENCES USUARIO(cod_usuario) ON DELETE CASCADE
);

CREATE INDEX idx_password_reset_usuario
    ON PASSWORD_RESET_TOKEN (cod_usuario, expires_at);
