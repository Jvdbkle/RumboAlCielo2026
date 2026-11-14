<?php
/**
 * HU-14: el empleado consulta el menú de la semana con las fotos de cada día.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\ResultadoMenu $resultado
 * @var DateTimeImmutable $anterior
 * @var DateTimeImmutable $siguiente
 */
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Reloj;

$titulo = 'Menú de la semana';
$nombresDia = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves'];
$mesesCortos = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$hoy = Reloj::ahora()->format('Y-m-d');

// Los cuatro días de la semana; si un día no tiene plato (feriado), se muestra "sin servicio".
$platosPorFecha = [];
foreach ($resultado->dias as $plato) {
    $platosPorFecha[$plato->fecha->format('Y-m-d')] = $plato;
}

$enlacesVolver = [['Volver al panel', '/empleado']];
$lunes = $resultado->lunes;
$tituloBanner = 'Menú de la semana';
$ruta = '/empleado/menu';
$estadoSemana = $resultado->publicado ? ['texto' => 'Publicado', 'clase' => 'ok'] : null;
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina ancha">
                <?php require __DIR__ . '/partes/volver.php'; ?>
        <?php require __DIR__ . '/partes/barra_semana.php'; ?>

        <?php if (!$resultado->publicado): ?>
            <section class="menu-pendiente <?= $resultado->aviso !== '' ? 'en-proceso' : '' ?>" role="status">
                <span class="menu-pendiente-icono" aria-hidden="true"><?= $resultado->aviso !== '' ? "\u{23F3}" : "\u{1F4C5}" ?></span>
                <h2><?= $resultado->aviso !== '' ? 'Estamos preparando el menú' : 'El menú de esta semana aún no está listo' ?></h2>
                <p><?= $h($resultado->aviso !== '' ? $resultado->aviso : 'Cuando el administrador lo publique, lo verás aquí con las fotos de cada plato.') ?></p>
            </section>
        <?php else: ?>
            <nav class="pestanas-dia" aria-label="Días de la semana">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <?php $fechaDia = $resultado->lunes->modify('+' . $i . ' day'); ?>
                    <button type="button" data-dia="<?= $i ?>" data-fecha="<?= $fechaDia->format('Y-m-d') ?>">
                        <?= $nombresDia[$i + 1] ?>
                        <small><?= $fechaDia->format('j') ?> <?= $mesesCortos[(int) $fechaDia->format('n')] ?></small>
                    </button>
                <?php endfor; ?>
            </nav>

            <div class="carta">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <?php
                    $fechaDia = $resultado->lunes->modify('+' . $i . ' day');
                    $fecha = $fechaDia->format('Y-m-d');
                    $plato = $platosPorFecha[$fecha] ?? null;
                    $esHoy = $fecha === $hoy;
                    $pasado = $fecha < $hoy;
                    ?>
                    <article class="carta-dia<?= $esHoy ? ' es-hoy' : '' ?><?= $pasado ? ' pasado' : '' ?>" data-dia="<?= $i ?>" style="--i: <?= $i ?>">
                        <header>
                            <h2><?= $nombresDia[$i + 1] ?></h2>
                            <span class="fecha"><?= $fechaDia->format('j') ?> <?= $mesesCortos[(int) $fechaDia->format('n')] ?></span>
                            <?php if ($esHoy): ?><span class="marca-hoy">Hoy</span><?php endif; ?>
                        </header>

                        <?php if ($plato === null): ?>
                            <div class="dia-feriado">
                                <strong>Sin servicio</strong>
                                <span>Este día no hay almuerzo</span>
                            </div>
                        <?php else: ?>
                            <?php
                            $filas = [
                                ['Sopa', $plato->sopa, $plato->fotoSopa, "\u{1F372}"],
                                ['Plato principal', $plato->platoPrincipal, $plato->fotoPlatoPrincipal, "\u{1F35B}"],
                                ['Ensalada o guarnición', $plato->ensaladaGuarnicion, '', "\u{1F957}"],
                                ['Postre', $plato->postre, '', "\u{1F370}"],
                                ['Refresco', $plato->refresco, '', "\u{1F964}"],
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
                            </footer>
                        <?php endif; ?>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>