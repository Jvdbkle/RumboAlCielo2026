<?php
/**
 * HU-09 y HU-10: solicitudes de pensión por confirmar o rechazar y pensiones activas por revocar.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaPension[] $pendientes
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaPension[] $activas
 * @var array{tipo: string, texto: string}|null $aviso
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Pensiones';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Administración';
$tituloPagina = 'Pensiones';
$subtituloPagina = 'Confirma o rechaza las solicitudes de pensión y revoca las registradas por error.';
$accionPagina = null;

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= $h($aviso['texto']) ?></div>
        <?php endif; ?>

        <h2 class="titulo-grupo">Solicitudes pendientes <span class="etiqueta pendiente"><?= count($pendientes) ?></span></h2>
        <?php if ($pendientes === []): ?>
            <p class="vacio-simple">No hay solicitudes pendientes por revisar.</p>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Persona</th>
                            <th>Titular</th>
                            <th>Mes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendientes as $fila): ?>
                        <tr>
                            <td><strong><?= $h($fila->nombrePersona) ?></strong></td>
                            <td><?= $h($fila->nombreTitular) ?></td>
                            <td><?= $meses[(int) $fila->mes->format('n')] ?> <?= $fila->mes->format('Y') ?></td>
                            <td>
                                <div class="acciones">
                                    <form method="post" action="/administrador/pensiones/confirmar">
                                        <?= TokenCsrf::campo() ?>
                                        <input type="hidden" name="idPension" value="<?= $fila->idPension ?>">
                                        <button type="submit" class="chico">Confirmar</button>
                                    </form>
                                    <form method="post" action="/administrador/pensiones/rechazar">
                                        <?= TokenCsrf::campo() ?>
                                        <input type="hidden" name="idPension" value="<?= $fila->idPension ?>">
                                        <button type="submit" class="chico peligro">Rechazar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="titulo-grupo">Pensiones activas <span class="etiqueta ok"><?= count($activas) ?></span></h2>
        <?php if ($activas === []): ?>
            <p class="vacio-simple">Todavía no hay pensiones activas.</p>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Persona</th>
                            <th>Titular</th>
                            <th>Mes</th>
                            <th>Almuerzos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activas as $fila): ?>
                        <tr>
                            <td><strong><?= $h($fila->nombrePersona) ?></strong></td>
                            <td><?= $h($fila->nombreTitular) ?></td>
                            <td><?= $meses[(int) $fila->mes->format('n')] ?> <?= $fila->mes->format('Y') ?></td>
                            <td><?= $fila->reservados ?> reservados · <?= $fila->recogidos ?> recogidos</td>
                            <td>
                                <a class="boton chico peligro" data-ventana href="/administrador/pensiones/revocar?id=<?= $fila->idPension ?>">Revocar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>