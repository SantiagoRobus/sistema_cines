<?php

function tipos_beneficio(): array
{
    return [
        'estudiante' => 'Estudiante',
        'pensionado' => 'Pensionado',
        'servicio_militar' => 'Servicio militar',
        'discapacidad' => 'Persona con discapacidad',
    ];
}

function directorio_soportes_beneficio(): string
{
    $base = getenv('PRIVATE_STORAGE_PATH') ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cinex-private';
    if (!is_dir($base) && !mkdir($base, 0700, true) && !is_dir($base)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento privado de soportes.');
    }

    $realBase = realpath($base);
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($realBase === false || ($documentRoot !== false && str_starts_with(
        strtolower($realBase . DIRECTORY_SEPARATOR),
        strtolower($documentRoot . DIRECTORY_SEPARATOR)
    ))) {
        throw new RuntimeException('El almacenamiento privado debe estar fuera de la carpeta pública.');
    }

    $directorio = $realBase . DIRECTORY_SEPARATOR . 'beneficios';
    if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento privado de soportes.');
    }

    return $directorio;
}

function guardar_soporte_beneficio(array $archivo): array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
        throw new RuntimeException('Selecciona un documento válido para revisar.');
    }

    if (($archivo['size'] ?? 0) < 1 || $archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('El archivo debe pesar como máximo 5 MB.');
    }

    $tiposPermitidos = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    if (!isset($tiposPermitidos[$mime])) {
        throw new RuntimeException('Usa un soporte PDF, JPG o PNG.');
    }

    $clave = bin2hex(random_bytes(32)) . '.' . $tiposPermitidos[$mime];
    $destino = directorio_soportes_beneficio() . DIRECTORY_SEPARATOR . $clave;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar el soporte de manera privada.');
    }

    return ['clave' => $clave, 'mime' => $mime, 'ruta' => $destino];
}
