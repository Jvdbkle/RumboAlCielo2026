<?php
/**
 * HU-24: lista imprimible del día con casillas de control manual.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\ListaDiaria|null $lista
 * @var string $error
 */
$titulo = 'Lista del día';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Reportes';
$tituloPagina = 'Lista del día';
$subtituloPagina = 'Respaldo en papel por si falla el escaneo del código QR.';
$accionPagina = null;

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$dias = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <div class="no-imprimir">
            <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>
        </div>

        <?php if ($error !== ''): ?>
            <div class="aviso error" role="alert"><?= $h($error) ?></div>
        <?php else: ?>
            <div class="no-imprimir acciones-lista">
                <button type="button" class="chico" onclick="window.print()">Descargar / imprimir lista</button>
            </div>

            <section class="hoja-lista" aria-label="Lista del día">
                <h2>Almuerzos del <?= $dias[(int) $lista->fecha->format('N')] ?> <?= $lista->fecha->format('d/m/Y') ?></h2>
                <p class="total-lista">Total de almuerzos: <strong><?= $lista->total ?></strong></p>

                <?php if ($lista->total === 0): ?>
                    <p class="vacio-simple">No hay reservas para hoy.</p>
                <?php endif; ?>

                <?php foreach ($lista->grupos as $grupo): ?>
                    <div class="grupo-lista">
                        <h3><?= $h($grupo->nombreTitular) ?></h3>
                        <ul>
                        <?php foreach ($grupo->personas as $persona): ?>
                            <li class="<?= $persona->esTitular ? '' : 'familiar' ?>">
                                <span class="casilla" aria-hidden="true"></span>
                                <?= $h($persona->nombre) ?><?= $persona->esTitular ? '' : ' <small>(familiar)</small>' ?>
                                <?php if ($persona->entregada): ?><small class="entregada-lista">entregado</small><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>