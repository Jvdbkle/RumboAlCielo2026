<?php
/**
 * HU-28: el administrador consulta las reservas de una fecha o un rango, con filtro por empleado.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaReservaAdmin[] $filas
 * @var DateTimeImmutable $desde
 * @var DateTimeImmutable|null $hasta
 * @var int|null $idEmpleado
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaUsuario[] $empleados
 * @var string $error
 * @var string $sinResultados
 */
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;

$titulo = 'Reservas';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Administración';
$tituloPagina = 'Reservas';
$subtituloPagina = 'Consulta quién reservó, de qué empleado es cada almuerzo y si ya fue entregado.';
$accionPagina = ['texto' => 'Mostrador de entrega', 'href' => '/administrador/mostrador', 'ventana' => false];

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <form class="filtros" method="get" action="/administrador/reservas">
            <div>
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" value="<?= $desde->format('Y-m-d') ?>">
            </div>
            <div>
                <label for="hasta">Hasta (opcional)</label>
                <input type="date" id="hasta" name="hasta" value="<?= $hasta?->format('Y-m-d') ?>">
            </div>
            <div>
                <label for="empleado">Empleado</label>
                <select id="empleado" name="empleado">
                    <option value="">Todos</option>
                    <?php foreach ($empleados as $empleado): ?>
                        <option value="<?= $empleado->idUsuario ?>" <?= $empleado->idUsuario === $idEmpleado ? 'selected' : '' ?>><?= $h($empleado->nombreCompleto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Consultar</button>
        </form>

        <?php if ($error !== ''): ?>
            <div class="aviso error" role="alert"><?= $h($error) ?></div>
        <?php endif; ?>

        <?php if ($sinResultados !== ''): ?>
            <p class="vacio-simple"><?= $h($sinResultados) ?></p>
        <?php elseif ($filas !== []): ?>
            <h2 class="titulo-grupo"><?= count($filas) ?> reserva<?= count($filas) === 1 ? '' : 's' ?></h2>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr><th>Fecha</th><th>Persona</th><th>Empleado titular</th><th>Modalidad</th><th>Importe</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td><?= $fila->fecha->format('d/m/Y') ?></td>
                            <td><strong><?= $h($fila->nombrePersona) ?></strong></td>
                            <td><?= $h($fila->nombreTitular) ?></td>
                            <td><?= $h($fila->modalidad->value) ?></td>
                            <td><?= number_format($fila->importe, 2) ?> Bs</td>
                            <td><span class="etiqueta <?= $fila->estado === EstadoReserva::Entregada ? 'ok' : 'pendiente' ?>"><?= $h($fila->estado->value) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>