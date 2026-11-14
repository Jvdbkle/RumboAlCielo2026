<?php
/**
 * HU-08 criterio 1: formulario para solicitar la pensión del mes siguiente para el empleado
 * y sus familiares.
 *
 * @var string $mesTexto Mes que se solicita, por ejemplo "diciembre de 2026".
 * @var string $nombreEmpleado
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaFamiliar[] $familiaresFilas
 * @var bool $paraMi
 * @var int[] $marcados Familiares elegidos.
 * @var string $error Mensaje de error (vacío si no hay).
 */
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Solicitar pensión';
$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="/empleado/pensiones/solicitar" novalidate>
            <?= TokenCsrf::campo() ?>

            <h1>Solicitar pensión</h1>
            <p class="ayuda">
                Pensión de <strong><?= $h($mesTexto) ?></strong> a <?= number_format(Pension::TARIFA, 0) ?> Bs por almuerzo.
                El administrador debe confirmarla.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= $h($error) ?></div>
            <?php endif; ?>

            <p class="opciones-titulo">¿Quiénes tendrán pensión?</p>
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
                <p class="ayuda">No tienes familiares registrados. Pide al administrador que los agregue para incluirlos.</p>
            <?php endif; ?>

            <div class="botones">
                <button type="submit">Enviar solicitud</button>
                <a class="boton secundario" href="/empleado/pensiones">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>