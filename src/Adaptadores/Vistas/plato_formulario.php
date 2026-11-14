<?php
/**
 * HU-12 criterios 1 y 3: formulario para registrar o editar el plato de un día.
 *
 * @var string $titulo
 * @var string $accion
 * @var DateTimeImmutable $fecha Día de atención del plato.
 * @var bool $esEdicion
 * @var array<string, string> $valores
 * @var string $error Mensaje de error (vacío si no hay).
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$nombresDia = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$campos = [
    'sopa' => 'Sopa',
    'platoPrincipal' => 'Plato principal',
    'ensaladaGuarnicion' => 'Ensalada o guarnición',
    'postre' => 'Postre',
    'refresco' => 'Refresco',
];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="<?= $h($accion) ?>" enctype="multipart/form-data" novalidate>
            <?= TokenCsrf::campo() ?>
            <input type="hidden" name="fecha" value="<?= $fecha->format('Y-m-d') ?>">

            <h1><?= $h($titulo) ?></h1>
            <p class="ayuda"><?= $nombresDia[(int) $fecha->format('N')] ?> <?= $fecha->format('d/m/Y') ?></p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= $h($error) ?></div>
            <?php endif; ?>

            <?php foreach ($campos as $nombre => $etiqueta): ?>
                <label for="<?= $nombre ?>"><?= $etiqueta ?></label>
                <input type="text" id="<?= $nombre ?>" name="<?= $nombre ?>" maxlength="100" required
                       value="<?= $h($valores[$nombre]) ?>">
            <?php endforeach; ?>

            <label for="fotoSopa">Foto de la sopa (JPG o PNG, hasta 5 MB)</label>
            <input type="file" id="fotoSopa" name="fotoSopa" accept="image/jpeg,image/png">

            <label for="fotoPlatoPrincipal">Foto del plato principal (JPG o PNG, hasta 5 MB)</label>
            <input type="file" id="fotoPlatoPrincipal" name="fotoPlatoPrincipal" accept="image/jpeg,image/png">

            <?php if ($esEdicion): ?>
                <p class="ayuda izquierda">Si no eliges una foto nueva, se conserva la actual.</p>
            <?php endif; ?>

            <div class="botones">
                <button type="submit">Guardar</button>
                <a class="boton secundario" href="/administrador/menu?semana=<?= $fecha->format('Y-m-d') ?>">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>