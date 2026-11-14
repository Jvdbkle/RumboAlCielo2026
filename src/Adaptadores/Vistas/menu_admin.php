<?php
/**
 * HU-12 y HU-13: los cuatro días de la semana con su plato. Desde aquí se registra o edita el
 * plato de cada día y se publica el menú.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\ResultadoSemana $semana
 * @var DateTimeImmutable $anterior
 * @var DateTimeImmutable $siguiente
 * @var array{tipo: string, texto: string}|null $aviso
 */
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Menú semanal';
$nombresDia = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves'];
$mesesCortos = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$hoy = Reloj::ahora()->format('Y-m-d');

// Progreso: los días con servicio (sin feriado) que ya tienen plato.
$conServicio = 0;
$conPlato = 0;
$faltan = [];
foreach ($semana->dias as $dia) {
    if ($dia->motivoFeriado !== null) {
        continue;
    }
    $conServicio++;
    if ($dia->plato !== null) {
        $conPlato++;
    } else {
        $faltan[] = $nombresDia[(int) $dia->fecha->format('N')];
    }
}
$completa = $conServicio > 0 && $conPlato === $conServicio;
$porcentaje = $conServicio > 0 ? (int) round($conPlato * 100 / $conServicio) : 0;

$lunes = $semana->lunes;
$tituloBanner = 'Menú semanal';
$ruta = '/administrador/menu';
$estadoSemana = ['texto' => $semana->publicado ? 'Publicado' : 'Sin publicar', 'clase' => $semana->publicado ? 'ok' : 'pendiente'];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina ancha">
        <?php require __DIR__ . '/partes/barra_semana.php'; ?>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= $h($aviso['texto']) ?></div>
        <?php endif; ?>

        <section class="progreso-semana" aria-label="Avance de la semana">
            <div class="progreso-texto">
                <strong><?= $conPlato ?> de <?= $conServicio ?> días con plato</strong>
                <span>
                    <?php if ($conServicio === 0): ?>
                        Esta semana no hay servicio.
                    <?php elseif ($completa): ?>
                        La semana está completa<?= $semana->publicado ? '' : ' y lista para publicar' ?>.
                    <?php else: ?>
                        Faltan: <?= $h(implode(', ', $faltan)) ?>.
                    <?php endif; ?>
                </span>
            </div>
            <div class="progreso-barra" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $porcentaje ?>">
                <i style="width: <?= $porcentaje ?>%"></i>
            </div>
        </section>

        <nav class="pestanas-dia" aria-label="Días de la semana">
            <?php foreach ($semana->dias as $i => $dia): ?>
                <button type="button" data-dia="<?= $i ?>" data-fecha="<?= $dia->fecha->format('Y-m-d') ?>">
                    <?= $nombresDia[(int) $dia->fecha->format('N')] ?>
                    <small><?= $dia->fecha->format('j') ?> <?= $mesesCortos[(int) $dia->fecha->format('n')] ?></small>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="carta">
            <?php foreach ($semana->dias as $i => $dia): ?>
                <?php
                $fecha = $dia->fecha->format('Y-m-d');
                $esHoy = $fecha === $hoy;
                $pasado = $fecha < $hoy;
                ?>
                <article class="carta-dia<?= $esHoy ? ' es-hoy' : '' ?><?= $pasado ? ' pasado' : '' ?>" data-dia="<?= $i ?>" style="--i: <?= $i ?>">
                    <header>
                        <h2><?= $nombresDia[(int) $dia->fecha->format('N')] ?></h2>
                        <span class="fecha"><?= $dia->fecha->format('j') ?> <?= $mesesCortos[(int) $dia->fecha->format('n')] ?></span>
                        <?php if ($esHoy): ?><span class="marca-hoy">Hoy</span><?php endif; ?>
                    </header>

                    <?php if ($dia->motivoFeriado !== null): ?>
                        <div class="dia-feriado">
                            <strong>Sin atención</strong>
                            <span><?= $h($dia->motivoFeriado) ?></span>
                        </div>
                    <?php elseif ($dia->plato === null): ?>
                        <a class="dia-vacio" data-ventana href="/administrador/platos/nuevo?fecha=<?= $fecha ?>">
                            <span class="mas">+</span>
                            <strong>Registrar plato</strong>
                            <small>Todavía no hay plato para este día</small>
                        </a>
                    <?php else: ?>
                        <?php
                        $filas = [
                            ['Sopa', $dia->plato->sopa, $dia->plato->fotoSopa, "\u{1F372}"],
                            ['Plato principal', $dia->plato->platoPrincipal, $dia->plato->fotoPlatoPrincipal, "\u{1F35B}"],
                            ['Ensalada o guarnición', $dia->plato->ensaladaGuarnicion, '', "\u{1F957}"],
                            ['Postre', $dia->plato->postre, '', "\u{1F370}"],
                            ['Refresco', $dia->plato->refresco, '', "\u{1F964}"],
                        ];
                        ?>
                        <ul class="filas-plato">
                            <?php foreach ($filas as $n => [$tipo, $nombre, $foto, $icono]): ?>
                                <li class="fila-plato">
                                    <?php if ($foto !== ''): ?>
                                        <button type="button" class="miniatura" data-zoom="/uploads/platos/<?= $h($foto) ?>" data-pie="<?= $h($tipo . ': ' . $nombre) ?>" aria-label="Ver foto de <?= $h($tipo) ?>">
                                            <img src="/uploads/platos/<?= $h($foto) ?>" alt="" loading="lazy">
                                        </button>
                                    <?php else: ?>
                                        <span class="miniatura emoji <?= $n % 2 === 0 ? 'tono-azul' : 'tono-verde' ?>" aria-hidden="true"><?= $icono ?></span>
                                    <?php endif; ?>
                                    <div>
                                        <small><?= $h($tipo) ?></small>
                                        <strong><?= $h($nombre) ?></strong>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <footer class="pie-dia">
                            <div class="precios">
                                <b><?= number_format(Reserva::TARIFA_DIARIA, 0) ?> Bs</b>
                                <small><?= number_format(Pension::TARIFA, 0) ?> Bs pensionado</small>
                            </div>
                            <a class="boton chico secundario" data-ventana href="/administrador/platos/editar?fecha=<?= $fecha ?>">Editar plato</a>
                        </footer>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($semana->tienePlatos()): ?>
            <form class="publicar<?= $completa && !$semana->publicado ? ' destacado' : '' ?>" method="post" action="/administrador/menu/publicar">
                <?= TokenCsrf::campo() ?>
                <input type="hidden" name="semana" value="<?= $semana->lunes->format('Y-m-d') ?>">
                <button type="submit"><?= $semana->publicado ? 'Publicar de nuevo' : 'Publicar menú de la semana' ?></button>
                <?php if (!$completa): ?>
                    <p class="ayuda izquierda">Puedes publicar ahora, pero aún faltan días por registrar.</p>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>