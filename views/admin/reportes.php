<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');
require_once '../../config/conexion.php';

function fecha_reporte_valida(string $valor): bool
{
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    $errores = DateTimeImmutable::getLastErrors();

    return $fecha instanceof DateTimeImmutable
        && $fecha->format('Y-m-d') === $valor
        && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0));
}

$hoy = new DateTimeImmutable('today');
$periodo = $_GET['periodo'] ?? 'semana';
$periodosPermitidos = ['semana', 'quincena', 'mes', 'personalizado'];
$errorFiltro = null;

if (!in_array($periodo, $periodosPermitidos, true)) {
    $periodo = 'semana';
}

if ($periodo === 'quincena') {
    $desde = $hoy->modify('-14 days');
    $hasta = $hoy;
} elseif ($periodo === 'mes') {
    $desde = $hoy->modify('first day of this month');
    $hasta = $hoy;
} elseif ($periodo === 'personalizado') {
    $desdeTexto = $_GET['desde'] ?? '';
    $hastaTexto = $_GET['hasta'] ?? '';
    if (
        is_string($desdeTexto) && is_string($hastaTexto)
        && fecha_reporte_valida($desdeTexto) && fecha_reporte_valida($hastaTexto)
        && $desdeTexto <= $hastaTexto
    ) {
        $desde = new DateTimeImmutable($desdeTexto);
        $hasta = new DateTimeImmutable($hastaTexto);
    } else {
        $errorFiltro = 'El rango personalizado no es válido. Selecciona fechas correctas y verifica el orden.';
        $desde = $hoy->modify('-6 days');
        $hasta = $hoy;
    }
} else {
    $periodo = 'semana';
    $desde = $hoy->modify('-6 days');
    $hasta = $hoy;
}

$busqueda = trim(is_string($_GET['buscar'] ?? null) ? $_GET['buscar'] : '');
$busqueda = substr($busqueda, 0, 100);
$estado = $_GET['estado'] ?? 'todos';
$estadosPermitidos = ['todos', 'Confirmada', 'Pendiente', 'Cancelada'];
if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'todos';
}
$tipoReporte = $_GET['tipo'] ?? 'detalle';
if (!in_array($tipoReporte, ['detalle', 'top10'], true)) {
    $tipoReporte = 'detalle';
}

$condiciones = [
    'r.fecha_reserva >= CAST(:desde AS TIMESTAMP)',
    'r.fecha_reserva < CAST(:hasta AS TIMESTAMP)',
];
$parametros = [
    ':desde' => $desde->format('Y-m-d'),
    ':hasta' => $hasta->modify('+1 day')->format('Y-m-d'),
];

if ($estado !== 'todos') {
    $condiciones[] = 'r.estado = :estado';
    $parametros[':estado'] = $estado;
}

if ($busqueda !== '') {
    $condiciones[] = '(u.nombre ILIKE :nombre OR u.apellidos ILIKE :apellidos OR u.email ILIKE :email OR pe.titulo ILIKE :pelicula OR c.nombre ILIKE :cine)';
    $termino = '%' . $busqueda . '%';
    $parametros[':nombre'] = $termino;
    $parametros[':apellidos'] = $termino;
    $parametros[':email'] = $termino;
    $parametros[':pelicula'] = $termino;
    $parametros[':cine'] = $termino;
}

$whereSql = implode(' AND ', $condiciones);
$fromSql = '
    FROM RESERVA r
    JOIN CLIENTE cl ON cl.cod_usuario = r.cod_cliente
    JOIN USUARIO u ON u.cod_usuario = cl.cod_usuario
    JOIN PASE p ON p.cod_pase = r.cod_pase
    JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
    JOIN CINE c ON c.cod_cine = p.cod_cine
    LEFT JOIN PAGO_RESERVA pago ON pago.cod_reserva = r.cod_reserva
';

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total_reservas,
                COUNT(*) FILTER (WHERE r.estado = 'Confirmada') AS confirmadas,
                COALESCE(SUM(CASE WHEN r.estado = 'Confirmada' THEN r.num_entradas ELSE 0 END), 0) AS entradas_vendidas,
                COALESCE(SUM(CASE WHEN r.estado = 'Confirmada' THEN r.precio_total ELSE 0 END), 0) AS total_recaudado
         $fromSql
         WHERE $whereSql"
    );
    $stmt->execute($parametros);
    $resumen = $stmt->fetch();

    if ($tipoReporte === 'top10') {
        $stmt = $pdo->prepare(
            "SELECT pe.titulo AS pelicula,
                    COUNT(*) FILTER (WHERE r.estado = 'Confirmada') AS reservas_confirmadas,
                    COALESCE(SUM(CASE WHEN r.estado = 'Confirmada' THEN r.num_entradas ELSE 0 END), 0) AS entradas_vendidas,
                    COALESCE(SUM(CASE WHEN r.estado = 'Confirmada' THEN r.precio_total ELSE 0 END), 0) AS total_recaudado
             $fromSql
             WHERE $whereSql
             GROUP BY pe.cod_pelicula, pe.titulo
             ORDER BY entradas_vendidas DESC, total_recaudado DESC, pe.titulo ASC
             LIMIT 10"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT r.cod_reserva, r.fecha_reserva, r.estado, r.num_entradas, r.precio_total,
                    u.nombre, u.apellidos, u.email, pe.titulo AS pelicula, c.nombre AS cine,
                    p.fecha AS fecha_funcion, p.hora, t.descripcion AS tarifa,
                    COALESCE(pago.estado, CASE WHEN r.cod_empleado IS NOT NULL THEN 'taquilla' ELSE 'sin pago' END) AS estado_pago
             $fromSql
             JOIN TARIFA t ON t.cod_tarifa = r.cod_tarifa
             WHERE $whereSql
             ORDER BY r.fecha_reserva DESC, r.cod_reserva DESC"
        );
    }
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('No se pudo generar el reporte administrativo: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo generar el reporte. Verifica que las migraciones de reservas y pagos estén aplicadas.');
}

function reporte_h($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Cinex+</title>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header report-header">
        <h1>Reportes de reservas y ventas</h1>
        <a href="dashboard_admin.php" class="btn-back">Volver al panel</a>
    </header>

    <main class="container">
        <form method="get" class="report-filters">
            <div class="form-group">
                <label for="periodo">Periodo</label>
                <select name="periodo" id="periodo">
                    <option value="semana" <?php echo $periodo === 'semana' ? 'selected' : ''; ?>>Últimos 7 días</option>
                    <option value="quincena" <?php echo $periodo === 'quincena' ? 'selected' : ''; ?>>Últimos 15 días</option>
                    <option value="mes" <?php echo $periodo === 'mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="personalizado" <?php echo $periodo === 'personalizado' ? 'selected' : ''; ?>>Rango personalizado</option>
                </select>
            </div>
            <div class="form-group report-date" id="fechaDesde">
                <label for="desde">Desde</label>
                <input type="date" name="desde" id="desde" value="<?php echo reporte_h($desde->format('Y-m-d')); ?>">
            </div>
            <div class="form-group report-date" id="fechaHasta">
                <label for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="<?php echo reporte_h($hasta->format('Y-m-d')); ?>">
            </div>
            <div class="form-group">
                <label for="buscar">Cliente, correo, película o cine</label>
                <input type="search" name="buscar" id="buscar" maxlength="100" value="<?php echo reporte_h($busqueda); ?>" placeholder="Ej. Ana, ana@correo.com o película">
            </div>
            <div class="form-group">
                <label for="estado">Estado de reserva</label>
                <select name="estado" id="estado">
                    <option value="todos" <?php echo $estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="Confirmada" <?php echo $estado === 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                    <option value="Pendiente" <?php echo $estado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Cancelada" <?php echo $estado === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tipo">Informe</label>
                <select name="tipo" id="tipo">
                    <option value="detalle" <?php echo $tipoReporte === 'detalle' ? 'selected' : ''; ?>>Detalle de reservas</option>
                    <option value="top10" <?php echo $tipoReporte === 'top10' ? 'selected' : ''; ?>>Top 10 películas vendidas</option>
                </select>
            </div>
            <div class="report-actions">
                <button type="submit" class="btn-submit">Generar informe</button>
                <button type="button" class="btn-back" onclick="window.print()">Imprimir</button>
                <a class="btn-back" href="reportes.php">Limpiar filtros</a>
            </div>
        </form>

        <?php if ($errorFiltro): ?>
            <p class="notice" role="alert"><?php echo reporte_h($errorFiltro); ?></p>
        <?php endif; ?>

        <p class="report-period">
            Resultados del <?php echo reporte_h($desde->format('d/m/Y')); ?>
            al <?php echo reporte_h($hasta->format('d/m/Y')); ?>
            <?php if ($busqueda !== ''): ?> · Búsqueda: “<?php echo reporte_h($busqueda); ?>”<?php endif; ?>
        </p>

        <section class="report-summary" aria-label="Resumen del periodo">
            <article><span>Reservas encontradas</span><strong><?php echo (int) $resumen['total_reservas']; ?></strong></article>
            <article><span>Reservas confirmadas</span><strong><?php echo (int) $resumen['confirmadas']; ?></strong></article>
            <article><span>Entradas vendidas</span><strong><?php echo (int) $resumen['entradas_vendidas']; ?></strong></article>
            <article><span>Recaudo confirmado</span><strong>$<?php echo number_format((float) $resumen['total_recaudado'], 0, ',', '.'); ?> COP</strong></article>
        </section>

        <h2><?php echo $tipoReporte === 'top10' ? 'Top 10 películas por entradas vendidas' : 'Detalle de reservas'; ?></h2>
        <div class="report-table">
            <?php if ($tipoReporte === 'top10'): ?>
                <table>
                    <thead><tr><th>Puesto</th><th>Película</th><th>Reservas confirmadas</th><th>Entradas vendidas</th><th>Recaudo</th></tr></thead>
                    <tbody>
                    <?php foreach ($filas as $indice => $fila): ?>
                        <tr>
                            <td><?php echo $indice + 1; ?></td>
                            <td><?php echo reporte_h($fila['pelicula']); ?></td>
                            <td><?php echo (int) $fila['reservas_confirmadas']; ?></td>
                            <td><?php echo (int) $fila['entradas_vendidas']; ?></td>
                            <td>$<?php echo number_format((float) $fila['total_recaudado'], 0, ',', '.'); ?> COP</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$filas): ?><tr><td colspan="5">No hay ventas en los filtros seleccionados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table>
                    <thead><tr><th>Reserva</th><th>Fecha</th><th>Cliente</th><th>Película / cine</th><th>Función</th><th>Tarifa</th><th>Entradas</th><th>Total</th><th>Estado</th><th>Pago</th></tr></thead>
                    <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td>#<?php echo (int) $fila['cod_reserva']; ?></td>
                            <td><?php echo reporte_h(date('d/m/Y H:i', strtotime($fila['fecha_reserva']))); ?></td>
                            <td><?php echo reporte_h($fila['nombre'] . ' ' . $fila['apellidos']); ?><br><small><?php echo reporte_h($fila['email']); ?></small></td>
                            <td><?php echo reporte_h($fila['pelicula']); ?><br><small><?php echo reporte_h($fila['cine']); ?></small></td>
                            <td><?php echo reporte_h(date('d/m/Y', strtotime($fila['fecha_funcion'])) . ' ' . substr((string) $fila['hora'], 0, 5)); ?></td>
                            <td><?php echo reporte_h($fila['tarifa']); ?></td>
                            <td><?php echo (int) $fila['num_entradas']; ?></td>
                            <td>$<?php echo number_format((float) $fila['precio_total'], 0, ',', '.'); ?> COP</td>
                            <td><?php echo reporte_h($fila['estado']); ?></td>
                            <td><?php echo reporte_h($fila['estado_pago']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$filas): ?><tr><td colspan="10">No hay reservas en los filtros seleccionados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    <script>
        const periodo = document.getElementById('periodo');
        const camposFecha = document.querySelectorAll('.report-date');
        function actualizarFechas() {
            camposFecha.forEach(campo => {
                campo.hidden = periodo.value !== 'personalizado';
            });
        }
        periodo.addEventListener('change', actualizarFechas);
        actualizarFechas();
    </script>
</body>
</html>
