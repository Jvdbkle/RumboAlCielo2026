<?php
/**
 * HU-19: calendario mensual del empleado. Muestra de lunes a jueves el estado de cada día
 * (libre, confirmada, entregada o feriado) con las reservas propias y las de sus familiares.
 * Desde aquí se reserva (HU-16, HU-17) y se cancela (HU-18).
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\DiaDelCalendario[] $dias
 * @var DateTimeImmutable $mes Primer día del mes que se muestra.
 * @var DateTimeImmutable $anterior
 * @var DateTimeImmutable $siguiente
 * @var array{tipo: string, texto: string}|null $aviso
 */
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Reloj;

$titulo = 'Mis reservas';
$enlacesVolver = [['Volver al panel', '/empleado']];
$sobreTitulo = 'Mis almuerzos';
$tituloPagina = 'Mis reservas';
$subtituloPagina = 'Reserva para ti y tus familiares hasta las 09:00 del día del almuerzo.';
$accionPagina = ['texto' => 'Mis códigos QR', 'href' => '/empleado/qr', 'ventana' => false];

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mesesCortos = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$nombresDia = [1 => 'Lun', 'Mar', 'Mié', 'Jue'];
$claveMes = $mes->format('Y-m');
$hoy = Reloj::ahora()->format('Y-m-d');
$esteMes = Reloj::ahora()->format('Y-m');

// Se agrupan los días por semana para mostrar una fila por semana.
$semanas = [];
foreach ($dias as $dia) {
    $lunes = $dia->fecha->modify('monday this week')->format('Y-m-d');
    $semanas[$lunes][(int) $dia->fecha->format('N')] = $dia;
}
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= $h($aviso['texto']) ?></div>
        <?php endif; ?>

        <nav class="mes-nav" aria-label="Cambiar de mes">
            <a class="flecha-mes" href="/empleado/reservas?mes=<?= $anterior->format('Y-m') ?>" aria-label="Mes anterior">&#8249;</a>
            <div class="mes-titulo">
                <strong><?= $meses[(int) $mes->format('n')] ?> <?= $mes->format('Y') ?></strong>
                <?php if ($claveMes !== $esteMes): ?>
                    <a href="/empleado/reservas">Ir a este mes</a>
                <?php else: ?>
                    <span>Este mes</span>
                <?php endif; ?>
            </div>
            <a class="flecha-mes" href="/empleado/reservas?mes=<?= $siguiente->format('Y-m') ?>" aria-label="Mes siguiente">&#8250;</a>
        </nav>

        <ul class="leyenda-cal" aria-label="Significado de los colores">
            <li><span class="punto libre"></span> Libre</li>
            <li><span class="punto confirmada"></span> Confirmada</li>
            <li><span class="punto entregada"></span> Entregada</li>
            <li><span class="punto feriado"></span> Feriado</li>
        </ul>

        <?php $orden = 0; foreach ($semanas as $lunesTexto => $semana): ?>
            <?php $lunes = new DateTimeImmutable($lunesTexto); $jueves = $lunes->modify('+3 day'); ?>
            <h2 class="titulo-grupo">Semana del <?= $lunes->format('j') ?> <?= $mesesCortos[(int) $lunes->format('n')] ?> al <?= $jueves->format('j') ?> <?= $mesesCortos[(int) $jueves->format('n')] ?></h2>
            <div class="semana-cal">
                <?php for ($n = 1; $n <= 4; $n++): ?>
                    <?php if (!isset($semana[$n])): ?>
                        <div class="dia-cal fuera" aria-hidden="true"></div>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php
                    $dia = $semana[$n];
                    $fechaDia = $dia->fecha->format('Y-m-d');
                    $clases = 'dia-cal';
                    if ($dia->esFeriado()) {
                        $clases .= ' es-feriado';
                    } elseif ($dia->reservas !== []) {
                        $clases .= ' con-reservas';
                    }
                    if ($fechaDia === $hoy) {
                        $clases .= ' es-hoy';
                    } elseif ($fechaDia < $hoy) {
                        $clases .= ' pasado';
                    }
                    ?>
                    <article class="<?= $clases ?>" style="--i: <?= $orden++ ?>">
                        <header class="dia-cab">
                            <span class="dia-num"><?= $dia->fecha->format('j') ?></span>
                            <span class="dia-nombre"><?= $nombresDia[$n] ?><?= $fechaDia === $hoy ? ' · hoy' : '' ?></span>
                        </header>

                        <div class="dia-cuerpo">
                            <?php if ($dia->esFeriado()): ?>
                                <span class="etiqueta baja">Feriado</span>
                                <p class="dia-nota"><?= $h($dia->motivoFeriado) ?></p>
                            <?php elseif ($dia->reservas === []): ?>
                                <span class="dia-libre">Libre</span>
                                <?php if (!$dia->hayMenu && $fechaDia >= $hoy): ?>
                                    <p class="dia-nota">Aún sin menú publicado</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <ul class="reservas-dia">
                                    <?php foreach ($dia->reservas as $reserva): ?>
                                        <li>
                                            <div class="reserva-fila">
                                                <strong><?= $h($reserva->nombrePersona) ?></strong>
                                                <span class="etiqueta <?= $reserva->estado === EstadoReserva::Entregada ? 'ok' : 'confirmada' ?>"><?= $reserva->estado->value ?></span>
                                            </div>
                                            <div class="reserva-pie">
                                                <span><?= number_format($reserva->importe, 0) ?> Bs</span>
                                                <?php if ($reserva->puedeCancelarse): ?>
                                                    <a class="cancelar-enlace" data-ventana href="/empleado/reservas/cancelar?id=<?= $reserva->idReserva ?>&amp;mes=<?= $claveMes ?>">Cancelar</a>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <?php if ($dia->abierto): ?>
                            <a class="boton chico dia-reservar" data-ventana href="/empleado/reservas/reservar?fecha=<?= $fechaDia ?>">Reservar</a>
                        <?php endif; ?>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>