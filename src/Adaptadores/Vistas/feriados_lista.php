<?php
/**
 * HU-15: días sin atención registrados. Desde aquí se registra uno nuevo.
 *
 * @var \RumboAlCielo\Dominio\Entidades\Feriado[] $feriados
 * @var array{tipo: string, texto: string}|null $aviso
 */
use RumboAlCielo\Dominio\Entidades\Feriado;
use RumboAlCielo\Dominio\Reloj;

$titulo = 'Feriados';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Calendario del comedor';
$tituloPagina = 'Feriados y días sin atención';
$subtituloPagina = 'En estas fechas no hay menú ni se pueden hacer reservas.';
$accionPagina = ['texto' => 'Registrar día sin atención', 'href' => '/administrador/feriados/nuevo', 'ventana' => true];

$hoy = Reloj::ahora()->setTime(0, 0);
$nombresDia = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
$mesesCortos = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

// Los que vienen, del más cercano al más lejano; los pasados, del más reciente al más antiguo.
$proximos = [];
$pasados = [];
foreach ($feriados as $feriado) {
    if ($feriado->getFecha() >= $hoy) {
        $proximos[] = $feriado;
    } else {
        array_unshift($pasados, $feriado);
    }
}

$tarjeta = function (Feriado $feriado, bool $pasado, int $orden) use ($hoy, $nombresDia, $mesesCortos): void {
    $fecha = $feriado->getFecha();
    $dias = (int) $hoy->diff($fecha)->days;
    if ($pasado) {
        $cuando = $dias === 1 ? 'Ayer' : 'Hace ' . $dias . ' días';
    } elseif ($dias === 0) {
        $cuando = 'Hoy';
    } elseif ($dias === 1) {
        $cuando = 'Mañana';
    } else {
        $cuando = 'En ' . $dias . ' días';
    }
    ?>
        <article class="feriado-tarjeta<?= $pasado ? ' pasado' : '' ?>" style="--i: <?= $orden ?>">
            <div class="tile-fecha" aria-hidden="true">
                <span class="tile-mes"><?= $mesesCortos[(int) $fecha->format('n')] ?></span>
                <span class="tile-dia"><?= $fecha->format('j') ?></span>
            </div>
            <div class="feriado-info">
                <strong><?= htmlspecialchars($feriado->getMotivo(), ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= ucfirst($nombresDia[(int) $fecha->format('N')]) ?> <?= $fecha->format('d/m/Y') ?></small>
                <span class="etiqueta <?= $pasado ? 'baja' : 'pendiente' ?>"><?= $cuando ?></span>
            </div>
        </article>
    <?php
};
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status">
                <?= htmlspecialchars($aviso['texto'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($feriados === []): ?>
            <section class="vacio-grande">
                <span class="vacio-icono" aria-hidden="true">&#128197;</span>
                <h2>Todavía no hay días sin atención</h2>
                <p>Registra los feriados antes de armar el menú, así esas fechas quedan bloqueadas.</p>
                <a class="boton" data-ventana href="/administrador/feriados/nuevo">Registrar día sin atención</a>
            </section>
        <?php else: ?>
            <div class="resumen-feriados">
                <span class="etiqueta pendiente"><?= count($proximos) ?> <?= count($proximos) === 1 ? 'próximo' : 'próximos' ?></span>
                <span class="etiqueta baja"><?= count($pasados) ?> <?= count($pasados) === 1 ? 'pasado' : 'pasados' ?></span>
            </div>

            <?php if ($proximos !== []): ?>
                <h2 class="titulo-grupo">Próximos</h2>
                <div class="feriados-grid">
                    <?php foreach ($proximos as $orden => $feriado) { $tarjeta($feriado, false, $orden); } ?>
                </div>
            <?php endif; ?>

            <?php if ($pasados !== []): ?>
                <h2 class="titulo-grupo">Ya pasaron</h2>
                <div class="feriados-grid">
                    <?php foreach ($pasados as $orden => $feriado) { $tarjeta($feriado, true, $orden); } ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>