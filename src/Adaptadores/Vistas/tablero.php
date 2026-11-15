<?php
/**
 * HU-23: tablero de indicadores del día.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\Tablero $tablero
 * @var DateTimeImmutable $hoy
 */
$titulo = 'Tablero';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Reportes';
$tituloPagina = 'Tablero del día';
$subtituloPagina = 'El estado del servicio de un vistazo.';
$accionPagina = ['texto' => 'Lista del día', 'href' => '/administrador/lista-diaria', 'ventana' => false];

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$tonoMenu = match ($tablero->estadoMenu) {
    'Publicado' => 'verde',
    'Pendiente' => 'amarillo',
    default => 'azul',
};
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <section class="estadisticas">
            <div class="estadistica azul"><span>Empleados habilitados</span><strong><?= $tablero->empleadosHabilitados ?></strong></div>
            <div class="estadistica <?= $tonoMenu ?>"><span>Menú de la semana</span><strong><?= $h($tablero->estadoMenu) ?></strong></div>
            <div class="estadistica"><span>Reservas de hoy</span><strong><?= $tablero->totalReservasHoy() ?></strong></div>
        </section>

        <h2 class="titulo-grupo">Reservas de hoy, <?= $hoy->format('d/m/Y') ?></h2>
        <div class="tabla-scroll">
            <table class="tabla">
                <thead><tr><th>Tipo</th><th>Total</th><th>Entregadas</th><th>Pendientes</th></tr></thead>
                <tbody>
                <?php foreach ([['Pensionados', $tablero->pensionados], ['No pensionados', $tablero->noPensionados]] as [$nombre, $conteo]): ?>
                    <tr>
                        <td><strong><?= $nombre ?></strong></td>
                        <td><?= $conteo->total ?></td>
                        <td><span class="etiqueta ok"><?= $conteo->entregadas ?></span></td>
                        <td><span class="etiqueta pendiente"><?= $conteo->pendientes ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($tablero->totalReservasHoy() === 0): ?>
            <p class="vacio-simple">Todavía no hay reservas para hoy.</p>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>