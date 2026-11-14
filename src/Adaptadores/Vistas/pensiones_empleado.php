<?php
/**
 * HU-11: el empleado consulta el estado de su pensión y la de sus familiares, con los
 * almuerzos reservados y recogidos. Desde aquí también solicita la pensión (HU-08).
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaPension[] $filas
 * @var array{tipo: string, texto: string}|null $aviso
 */
$titulo = 'Mis pensiones';
$enlacesVolver = [['Volver al panel', '/empleado']];
$sobreTitulo = 'Pensión mensual';
$tituloPagina = 'Mis pensiones';
$subtituloPagina = 'Almuerzos a 15 Bs por persona, sin reservar día por día.';
$accionPagina = ['texto' => 'Solicitar pensión', 'href' => '/empleado/pensiones/solicitar', 'ventana' => true];

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$detalle = [
    'Pendiente' => ['pendiente', 'Esperando la confirmación del administrador'],
    'Activa' => ['ok', 'Pensión activa: 15 Bs por almuerzo'],
    'Rechazada' => ['baja', 'El administrador rechazó la solicitud'],
];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= $h($aviso['texto']) ?></div>
        <?php endif; ?>

        <?php if ($filas === []): ?>
            <section class="vacio-grande">
                <span class="vacio-icono" aria-hidden="true">&#127858;</span>
                <h2>No tienes pensiones registradas</h2>
                <p>Solicita la pensión del próximo mes para ti y tus familiares y ahorra 3 Bs por almuerzo.</p>
                <a class="boton" data-ventana href="/empleado/pensiones/solicitar">Solicitar pensión</a>
            </section>
        <?php else: ?>
            <?php $mesActual = ''; $orden = 0; ?>
            <?php foreach ($filas as $fila): ?>
                <?php $mesFila = $meses[(int) $fila->mes->format('n')] . ' ' . $fila->mes->format('Y'); ?>
                <?php if ($mesFila !== $mesActual): ?>
                    <?php if ($mesActual !== ''): ?></div><?php endif; ?>
                    <h2 class="titulo-grupo"><?= $mesFila ?></h2>
                    <div class="pensiones-grid">
                    <?php $mesActual = $mesFila; ?>
                <?php endif; ?>
                <?php [$clase, $descripcion] = $detalle[$fila->estado->value]; ?>
                <article class="pension-tarjeta" style="--i: <?= $orden++ ?>">
                    <div class="pension-cabecera">
                        <strong><?= $h($fila->nombrePersona) ?></strong>
                        <span class="etiqueta <?= $clase ?>"><?= $h($fila->estado->value) ?></span>
                    </div>
                    <p class="pension-detalle"><?= $h($descripcion) ?></p>
                    <div class="contadores">
                        <div><b><?= $fila->reservados ?></b><span>Reservados</span></div>
                        <div><b><?= $fila->recogidos ?></b><span>Recogidos</span></div>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>