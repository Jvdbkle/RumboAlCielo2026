<?php
/**
 * HU-26 y HU-27: liquidación mensual por empleado y exportación de la planilla en Excel.
 *
 * @var DateTimeImmutable $mes
 * @var DateTimeImmutable $anterior
 * @var DateTimeImmutable $siguiente
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\ResultadoLiquidacion|null $resultado
 * @var array{tipo: string, texto: string}|null $aviso
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Liquidación mensual';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Reportes';
$tituloPagina = 'Liquidación mensual';
$subtituloPagina = 'Lo que se descuenta por planilla a cada empleado, con sus familiares.';
$accionPagina = null;

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$bs = fn (float $monto): string => number_format($monto, 2) . ' Bs';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= $h($aviso['texto']) ?></div>
        <?php endif; ?>

        <nav class="mes-nav" aria-label="Cambiar de mes">
            <a class="flecha-mes" href="/administrador/liquidacion?mes=<?= $anterior->format('Y-m') ?>" aria-label="Mes anterior">&#8249;</a>
            <div class="mes-titulo">
                <strong><?= $meses[(int) $mes->format('n')] ?> <?= $mes->format('Y') ?></strong>
                <?php if ($resultado === null): ?>
                    <span>Sin generar</span>
                <?php else: ?>
                    <span class="etiqueta <?= $resultado->estaCerrada() ? 'ok' : 'pendiente' ?>"><?= $h($resultado->estado->value) ?></span>
                <?php endif; ?>
            </div>
            <a class="flecha-mes" href="/administrador/liquidacion?mes=<?= $siguiente->format('Y-m') ?>" aria-label="Mes siguiente">&#8250;</a>
        </nav>

        <div class="acciones-liquidacion">
            <?php if ($resultado === null || !$resultado->estaCerrada()): ?>
                <form method="post" action="/administrador/liquidacion/generar">
                    <?= TokenCsrf::campo() ?>
                    <input type="hidden" name="mes" value="<?= $mes->format('Y-m') ?>">
                    <button type="submit" class="chico"><?= $resultado === null ? 'Generar liquidación' : 'Volver a calcular' ?></button>
                </form>
            <?php endif; ?>
            <?php if ($resultado !== null && !$resultado->estaCerrada()): ?>
                <form method="post" action="/administrador/liquidacion/cerrar">
                    <?= TokenCsrf::campo() ?>
                    <input type="hidden" name="mes" value="<?= $mes->format('Y-m') ?>">
                    <button type="submit" class="chico secundario">Cerrar mes</button>
                </form>
            <?php endif; ?>
            <a class="boton chico secundario" href="/administrador/liquidacion/planilla?mes=<?= $mes->format('Y-m') ?>">Exportar planilla Excel</a>
        </div>

        <?php if ($resultado === null): ?>
            <p class="vacio-simple">La liquidación de este mes todavía no fue generada.</p>
        <?php else: ?>
            <h2 class="titulo-grupo">Total a descontar por empleado</h2>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead><tr><th>Empleado titular</th><th>Total a descontar</th></tr></thead>
                    <tbody>
                    <?php foreach ($resultado->totalesPorEmpleado as $total): ?>
                        <tr><td><strong><?= $h($total['nombre']) ?></strong></td><td><?= $bs($total['total']) ?></td></tr>
                    <?php endforeach; ?>
                        <tr class="fila-total"><td>Total general</td><td><?= $bs($resultado->totalGeneral) ?></td></tr>
                    </tbody>
                </table>
            </div>

            <h2 class="titulo-grupo">Detalle por persona</h2>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead><tr><th>Persona</th><th>Titular</th><th>Modalidad</th><th>Almuerzos</th><th>Penalidad</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($resultado->filas as $fila): ?>
                        <tr>
                            <td><?= $h($fila->nombrePersona) ?></td>
                            <td><?= $h($fila->nombreTitular) ?></td>
                            <td><?= $h($fila->modalidad->value) ?></td>
                            <td><?= $fila->cantidad ?></td>
                            <td><?= $fila->penalidad > 0 ? $bs($fila->penalidad) : '—' ?></td>
                            <td><?= $bs($fila->total) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>