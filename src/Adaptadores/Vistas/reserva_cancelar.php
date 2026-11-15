<?php
/**
 * HU-18: confirmación antes de cancelar una reserva.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\ReservaDelCalendario $reserva
 * @var string $fechaTexto
 * @var DateTimeImmutable $mes
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Cancelar reserva';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="/empleado/reservas/cancelar">
            <?= TokenCsrf::campo() ?>
            <input type="hidden" name="idReserva" value="<?= $reserva->idReserva ?>">
            <input type="hidden" name="mes" value="<?= $mes->format('Y-m') ?>">

            <h1>Cancelar reserva</h1>
            <p class="ayuda">
                ¿Seguro que quieres cancelar el almuerzo de
                <strong><?= htmlspecialchars($reserva->nombrePersona, ENT_QUOTES, 'UTF-8') ?></strong>
                del <?= htmlspecialchars($fechaTexto, ENT_QUOTES, 'UTF-8') ?>?
                No se te cobrará ese almuerzo.
            </p>

            <div class="botones">
                <button type="submit" class="peligro">Sí, cancelar reserva</button>
                <a class="boton secundario" href="/empleado/reservas?mes=<?= $mes->format('Y-m') ?>">Mantener reserva</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>