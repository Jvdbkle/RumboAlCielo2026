<?php
/**
 * HU-21: códigos QR de las reservas confirmadas de hoy, uno por persona, para retirar el
 * almuerzo en el mostrador.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaCodigoQR[] $filas
 * @var string $hoyTexto
 */
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMisCodigosQR;
use RumboAlCielo\Infraestructura\Qr\CodigoQr;

$titulo = 'Mis códigos QR';
$enlacesVolver = [['Volver al panel', '/empleado'], ['Mis reservas', '/empleado/reservas']];
$sobreTitulo = 'Retiro del almuerzo';
$tituloPagina = 'Mis códigos QR';
$subtituloPagina = 'Hoy, ' . $hoyTexto . '. Muestra el código en el mostrador para retirar el almuerzo.';
$accionPagina = null;

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($filas === []): ?>
            <section class="vacio-grande">
                <span class="vacio-icono" aria-hidden="true">&#128247;</span>
                <h2><?= $h(ConsultarMisCodigosQR::SIN_RESERVA_HOY) ?></h2>
                <p>Reserva tu almuerzo antes de las 09:00 del día para que aparezca aquí tu código.</p>
                <a class="boton" href="/empleado/reservas">Ir a mis reservas</a>
            </section>
        <?php else: ?>
            <div class="qr-grid">
                <?php foreach ($filas as $orden => $fila): ?>
                    <article class="qr-tarjeta" style="--i: <?= $orden ?>">
                        <div class="qr-imagen"><?= CodigoQr::svg($fila->codigo, 'Código QR del almuerzo de ' . $fila->nombrePersona) ?></div>
                        <strong class="qr-nombre"><?= $h($fila->nombrePersona) ?></strong>
                        <span class="qr-detalle"><?= number_format($fila->importe, 0) ?> Bs · Reserva confirmada</span>
                        <code class="qr-codigo"><?= $h($fila->codigo) ?></code>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>