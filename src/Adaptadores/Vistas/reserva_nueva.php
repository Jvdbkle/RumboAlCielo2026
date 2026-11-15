<?php
/**
 * HU-16 y HU-17: formulario para reservar el almuerzo de un día para el empleado y sus familiares.
 *
 * @var DateTimeImmutable $fecha
 * @var string $fechaTexto Por ejemplo "lunes 16/11/2026".
 * @var bool $abierto Si todavía se puede reservar ese día.
 * @var string $nombreEmpleado
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaFamiliar[] $familiaresFilas
 * @var bool $paraMi
 * @var int[] $marcados Familiares elegidos.
 * @var string $error Mensaje de error (vacío si no hay).
 */
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Reservar almuerzo';
$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="/empleado/reservas/reservar" novalidate>
            <?= TokenCsrf::campo() ?>
            <input type="hidden" name="fecha" value="<?= $fecha->format('Y-m-d') ?>">

            <h1>Reservar almuerzo</h1>
            <p class="ayuda">
                Almuerzo del <strong><?= $h($fechaTexto) ?></strong>: <?= number_format(Reserva::TARIFA_DIARIA, 0) ?> Bs por persona
                (<?= number_format(Reserva::TARIFA_PENSIONADA, 0) ?> Bs si tiene pensión activa).
                Puedes reservar y cancelar hasta las 09:00 de ese día.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= $h($error) ?></div>
            <?php endif; ?>

            <?php if (!$abierto): ?>
                <div class="alerta" role="alert">Para ese día ya no se puede reservar: no hay menú publicado, es feriado o pasó la hora de corte.</div>
                <div class="botones">
                    <a class="boton secundario" href="/empleado/reservas?mes=<?= $fecha->format('Y-m') ?>">Volver al calendario</a>
                </div>
            <?php else: ?>
                <p class="opciones-titulo">¿Para quién es el almuerzo?</p>
                <div class="opciones">
                    <label class="opcion">
                        <input type="checkbox" name="paraMi" value="1" <?= $paraMi ? 'checked' : '' ?>>
                        <span><strong>Yo</strong> <small><?= $h($nombreEmpleado) ?></small></span>
                    </label>
                    <?php foreach ($familiaresFilas as $familiar): ?>
                        <label class="opcion">
                            <input type="checkbox" name="familiares[]" value="<?= $familiar->idFamiliar ?>"
                                   <?= in_array($familiar->idFamiliar, $marcados, true) ? 'checked' : '' ?>>
                            <span><strong><?= $h($familiar->nombreCompleto) ?></strong> <small><?= $h($familiar->parentesco) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if ($familiaresFilas === []): ?>
                    <p class="ayuda">No tienes familiares registrados. Pide al administrador que los agregue para reservar también para ellos.</p>
                <?php endif; ?>

                <div class="botones">
                    <button type="submit">Confirmar reserva</button>
                    <a class="boton secundario" href="/empleado/reservas?mes=<?= $fecha->format('Y-m') ?>">Cancelar</a>
                </div>
            <?php endif; ?>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>