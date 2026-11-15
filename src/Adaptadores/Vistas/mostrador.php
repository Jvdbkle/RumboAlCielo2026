<?php
/**
 * HU-22: mostrador de entrega. El administrador escanea o escribe el código QR y la pantalla
 * responde con una alerta visual (verde o roja) y un sonido.
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Mostrador de entrega';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Administración';
$tituloPagina = 'Mostrador de entrega';
$subtituloPagina = 'Escanea el código QR del empleado o escríbelo, y pulsa Validar.';
$accionPagina = ['texto' => 'Ver reservas', 'href' => '/administrador/reservas', 'ventana' => false];
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <form id="mostrador" class="tarjeta ancha mostrador-form" method="post" action="/administrador/mostrador/validar" autocomplete="off">
            <?= TokenCsrf::campo() ?>
            <label for="codigo">Código QR</label>
            <input type="text" id="codigo" name="codigo" maxlength="64" autofocus required placeholder="Escanea o escribe el código">
            <div class="botones">
                <button type="submit">Validar</button>
                <button type="button" id="usar-camara" class="secundario" hidden>Usar la cámara</button>
            </div>
            <video id="camara" class="mostrador-camara" playsinline muted hidden></video>
        </form>

        <div id="resultado" class="mostrador-resultado" role="status" aria-live="assertive" hidden>
            <strong id="resultado-titulo"></strong>
            <span id="resultado-detalle"></span>
        </div>
    </main>
    <script src="/js/mostrador.js" defer></script>
<?php require __DIR__ . '/partes/pie.php'; ?>