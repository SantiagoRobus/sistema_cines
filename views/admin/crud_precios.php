<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');
require_once '../../config/conexion.php';

try {
    $precios = $pdo->query(
        'SELECT pr.cod_cine, pr.cod_tarifa, pr.importe, c.nombre AS cine, t.descripcion AS tarifa
         FROM PRECIO pr JOIN CINE c ON c.cod_cine = pr.cod_cine
         JOIN TARIFA t ON t.cod_tarifa = pr.cod_tarifa
         ORDER BY c.nombre, t.descripcion'
    )->fetchAll();
    $cines = $pdo->query("SELECT cod_cine, nombre FROM CINE WHERE activo = '1' ORDER BY nombre")->fetchAll();
    $tarifas = $pdo->query('SELECT cod_tarifa, descripcion FROM TARIFA ORDER BY descripcion')->fetchAll();
} catch (PDOException $e) {
    error_log('No se pudieron cargar los precios: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las tarifas.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precios - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header">
        <h1>Precios por cine</h1>
        <a href="dashboard_admin.php" class="btn-back">Volver al panel</a>
    </header>
    <main class="container">
        <div class="table-header">
            <div>
                <h2>Tarifas en pesos colombianos</h2>
                <p>Configura el precio de cada tipo de entrada por sede.</p>
            </div>
            <button type="button" class="btn-add" id="addPrice">+ Configurar precio</button>
        </div>
        <?php if (!$cines || !$tarifas): ?>
            <p class="notice">Registra al menos un cine y una tarifa antes de configurar precios.</p>
        <?php endif; ?>
        <table>
            <thead><tr><th>Cine</th><th>Tipo de entrada</th><th>Precio</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($precios as $precio): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($precio['cine'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($precio['tarifa'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo number_format((float) $precio['importe'], 0, ',', '.'); ?> COP</td>
                        <td>
                            <button type="button" class="btn-action btn-edit edit-price"
                                data-cine="<?php echo (int) $precio['cod_cine']; ?>"
                                data-tarifa="<?php echo (int) $precio['cod_tarifa']; ?>"
                                data-importe="<?php echo htmlspecialchars((string) $precio['importe'], ENT_QUOTES, 'UTF-8'); ?>">Editar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$precios): ?><tr><td colspan="4">No hay precios configurados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </main>

    <div class="modal" id="priceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Configurar precio</h2>
                <button type="button" class="close" id="closeModal" aria-label="Cerrar">&times;</button>
            </div>
            <form action="../../auth/procesar_precios.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="cine_anterior" id="cineAnterior">
                <input type="hidden" name="tarifa_anterior" id="tarifaAnterior">
                <div class="form-group">
                    <label for="cine">Cine</label>
                    <select id="cine" name="cod_cine" required>
                        <?php foreach ($cines as $cine): ?>
                            <option value="<?php echo (int) $cine['cod_cine']; ?>"><?php echo htmlspecialchars($cine['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tarifa">Tipo de entrada</label>
                    <select id="tarifa" name="cod_tarifa" required>
                        <?php foreach ($tarifas as $tarifa): ?>
                            <option value="<?php echo (int) $tarifa['cod_tarifa']; ?>"><?php echo htmlspecialchars($tarifa['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="importe">Precio (COP)</label>
                    <input type="number" id="importe" name="importe" min="1" step="1" required>
                </div>
                <button type="submit" class="btn-submit">Guardar precio</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('priceModal');
        const form = modal.querySelector('form');
        const cinema = document.getElementById('cine');
        const fare = document.getElementById('tarifa');

        document.getElementById('addPrice').addEventListener('click', () => {
            form.reset();
            document.getElementById('modalTitle').textContent = 'Configurar precio';
            document.getElementById('accion').value = 'crear';
            document.getElementById('cineAnterior').value = '';
            document.getElementById('tarifaAnterior').value = '';
            modal.style.display = 'flex';
        });
        document.getElementById('closeModal').addEventListener('click', () => modal.style.display = 'none');

        document.querySelectorAll('.edit-price').forEach(button => button.addEventListener('click', () => {
            document.getElementById('modalTitle').textContent = 'Editar precio';
            document.getElementById('accion').value = 'editar';
            document.getElementById('cineAnterior').value = button.dataset.cine;
            document.getElementById('tarifaAnterior').value = button.dataset.tarifa;
            cinema.value = button.dataset.cine;
            fare.value = button.dataset.tarifa;
            document.getElementById('importe').value = button.dataset.importe;
            modal.style.display = 'flex';
        }));

        const messages = {
            saved: ['Precio guardado', 'La tarifa quedó disponible para las reservas.', 'success'],
            updated: ['Precio actualizado', 'Se guardaron los cambios.', 'success'],
            invalid: ['Datos inválidos', 'Completa los datos y verifica que el precio sea mayor a cero.', 'warning'],
            error: ['No se pudo guardar', 'Verifica que el cine y la tarifa sigan activos.', 'error']
        };
        const message = new URLSearchParams(location.search).get('msg');
        if (messages[message]) {
            Swal.fire({ title: messages[message][0], text: messages[message][1], icon: messages[message][2], confirmButtonColor: '#f6bd4b' });
            history.replaceState(null, '', location.pathname);
        }
    </script>
</body>
</html>
