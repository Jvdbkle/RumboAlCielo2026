<?php
/**
 * HU-10 criterio 1: confirmación antes de revocar una pensión.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaPension $fila
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Revocar pensión';
$meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="/administrador/pensiones/revocar">
            <?= TokenCsrf::campo() ?>
            <input type="hidden" name="idPension" value="<?= $fila->idPension ?>">
            <input type="hidden" name="confirmado" value="1">

            <h1>Revocar pensión</h1>
            <p class="ayuda">
                ¿Seguro que quieres revocar la pensión de
                <strong><?= htmlspecialchars($fila->nombrePersona, ENT_QUOTES, 'UTF-8') ?></strong>
                de <?= $meses[(int) $fila->mes->format('n')] ?> de <?= $fila->mes->format('Y') ?>?
                Sus reservas de ese mes se mantienen como reservas normales a 18 Bs.
            </p>

            <div class="botones">
                <button type="submit" class="peligro">Sí, revocar</button>
                <a class="boton secundario" href="/administrador/pensiones">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>