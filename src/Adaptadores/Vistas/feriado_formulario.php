<?php
/**
 * HU-15 criterio 1: formulario para registrar un día sin atención.
 *
 * @var array{fecha: string, motivo: string} $valores
 * @var string $error Mensaje de error (vacío si no hay).
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Registrar día sin atención';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="/administrador/feriados/nuevo" novalidate>
            <?= TokenCsrf::campo() ?>

            <h1>Registrar día sin atención</h1>
            <p class="ayuda">En esa fecha no habrá menú ni reservas.</p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" required autofocus
                   value="<?= htmlspecialchars($valores['fecha'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="motivo">Motivo</label>
            <input type="text" id="motivo" name="motivo" maxlength="100" required
                   placeholder="Por ejemplo: Día de la Patria"
                   value="<?= htmlspecialchars($valores['motivo'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="botones">
                <button type="submit">Guardar</button>
                <a class="boton secundario" href="/administrador/feriados">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>