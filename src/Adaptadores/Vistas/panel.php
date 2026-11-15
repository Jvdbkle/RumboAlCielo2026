<?php
/**
 * Pantalla de inicio de cada rol: saludo, resumen, menú de la semana y accesos a los módulos.
 *
 * @var string $titulo
 * @var string $rol
 * @var string $saludo
 * @var string $fechaHoy
 * @var string $mensaje
 * @var array<int, array{0: string, 1: string, 2: string}> $estadisticas Etiqueta, valor y color.
 * @var string $galeriaTitulo
 * @var string $galeriaVacia Mensaje cuando no hay platos que mostrar.
 * @var array<int, array<string, mixed>> $galeria Un día del menú por elemento.
 * @var string $galeriaRuta
 * @var string $galeriaEnlace
 * @var array<int, array{titulo: string, descripcion: string, ruta: string}> $modulos
 */
$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$destacada = null;
foreach ($galeria as $tarjeta) {
    if ($tarjeta['hoy']) {
        $destacada = $tarjeta;
    }
}
$destacada ??= $galeria[0] ?? null;

$iconos = [
    'Usuarios' => '<path d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 20c0-3.3 3.6-5 8-5s8 1.7 8 5v1H4v-1z"/>',
    'Menú semanal' => '<path d="M7 3v8a2 2 0 0 0 2 2v8h2V3H9v6H8V3H7zm9 0c-1.7 0-3 2.2-3 5s1.3 5 3 5v8h2V3h-2z"/>',
    'Menú de la semana' => '<path d="M7 3v8a2 2 0 0 0 2 2v8h2V3H9v6H8V3H7zm9 0c-1.7 0-3 2.2-3 5s1.3 5 3 5v8h2V3h-2z"/>',
    'Reservas' => '<path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm-2 8h14v9H5v-9zm3 2v2h2v-2H8z"/>',
    'Mostrador de entrega' => '<path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm8 0h2v2h-2v-2zm4 0h4v2h-4v-2zm-4 4h2v2h-2v-2zm4 0h4v2h-4v-2z"/>',
    'Tablero del día' => '<path d="M4 20V10h4v10H4zm6 0V4h4v16h-4zm6 0v-7h4v7h-4z"/>',
    'Lista del día' => '<path d="M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm2 4v2h2V7H7zm4 0v2h6V7h-6zm-4 4v2h2v-2H7zm4 0v2h6v-2h-6zm-4 4v2h2v-2H7zm4 0v2h6v-2h-6z"/>',
    'Exportar reservas' => '<path d="M12 3v10.2l3.6-3.6L17 11l-6 6-6-6 1.4-1.4L11 13.2V3h1zM5 19h14v2H5v-2z"/>',
    'Liquidación mensual' => '<circle cx="12" cy="12" r="9"/>',
    'Feriados' => '<path d="M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm-2 8h14v10H5V10z"/>',
    'Pensiones' => '<path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2H3V7zm0 4h18v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6zm13 2v2h3v-2h-3z"/>',
    'Mis pensiones' => '<path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2H3V7zm0 4h18v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6zm13 2v2h3v-2h-3z"/>',
    'Mis reservas' => '<path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm-2 8h14v9H5v-9zm3 2v2h2v-2H8z"/>',
    'Mi código QR' => '<path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm8 0h2v2h-2v-2zm4 0h4v2h-4v-2zm-4 4h2v2h-2v-2zm4 0h4v2h-4v-2z"/>',
    ];
$colores = ['naranja', 'azul', 'verde', 'amarillo'];

require __DIR__ . '/partes/encabezado.php';
?>
    <main class="inicio">
        <section class="hero">
            <div class="hero-texto">
                <span class="hero-rol"><?= $h($rol) ?></span>
                <h1><?= $h($saludo) ?></h1>
                <p class="hero-fecha"><?= $h($fechaHoy) ?></p>
                <p class="hero-mensaje"><?= $h($mensaje) ?></p>
                <?php if ($galeria !== []): ?>
                    <a class="boton-hero" href="<?= $h($galeriaRuta) ?>"><?= $h($galeriaEnlace) ?></a>
                <?php endif; ?>
            </div>
            <div class="hero-plato">
                <?php if ($destacada !== null): ?>
                    <img src="/uploads/platos/<?= $h((string) $destacada['fotoPrincipal']) ?>" alt="Plato principal: <?= $h((string) $destacada['principal']) ?>">
                    <span class="hero-etiqueta"><?= $destacada['hoy'] ? 'Plato de hoy' : $h((string) $destacada['dia']) ?> · <?= $h((string) $destacada['principal']) ?></span>
                <?php else: ?>
                    <svg viewBox="0 0 200 200" aria-hidden="true">
                        <circle cx="100" cy="100" r="92" fill="rgba(255,255,255,.10)"/>
                        <circle cx="100" cy="100" r="70" fill="rgba(255,255,255,.16)"/>
                        <circle cx="100" cy="100" r="46" fill="rgba(255,255,255,.22)"/>
                        <path d="M52 40v44a8 8 0 0 0 8 8v62h8V40h-6v38h-4V40h-6zm86 0c-10 0-18 14-18 34s8 34 18 34v54h8V40h-8z" fill="#f26b21" opacity=".9" transform="translate(4 4) scale(.95)"/>
                    </svg>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($estadisticas !== []): ?>
            <section class="estadisticas">
                <?php foreach ($estadisticas as [$etiqueta, $valor, $tono]): ?>
                    <div class="estadistica <?= $h($tono) ?>">
                        <span><?= $h($etiqueta) ?></span>
                        <strong><?= $h($valor) ?></strong>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section>
            <div class="seccion-titulo">
                <h2><?= $h($galeriaTitulo) ?></h2>
                <?php if ($galeria !== []): ?>
                    <a href="<?= $h($galeriaRuta) ?>"><?= $h($galeriaEnlace) ?> &rarr;</a>
                <?php endif; ?>
            </div>

            <?php if ($galeria === []): ?>
                <div class="galeria-vacia">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v8a2 2 0 0 0 2 2v8h2V3H9v6H8V3H7zm9 0c-1.7 0-3 2.2-3 5s1.3 5 3 5v8h2V3h-2z"/></svg>
                    <p><?= $h($galeriaVacia) ?></p>
                </div>
            <?php else: ?>
                <div class="galeria">
                    <?php foreach ($galeria as $tarjeta): ?>
                        <a class="plato-card <?= $tarjeta['hoy'] ? 'es-hoy' : '' ?>" href="<?= $h($galeriaRuta) ?>">
                            <div class="plato-fotos">
                                <img src="/uploads/platos/<?= $h((string) $tarjeta['fotoPrincipal']) ?>" alt="Plato principal">
                                <img src="/uploads/platos/<?= $h((string) $tarjeta['fotoSopa']) ?>" alt="Sopa">
                                <?php if ($tarjeta['hoy']): ?><span class="insignia">Hoy</span><?php endif; ?>
                            </div>
                            <div class="plato-info">
                                <span class="plato-dia"><?= $h((string) $tarjeta['dia']) ?> <?= $h((string) $tarjeta['fecha']) ?></span>
                                <strong><?= $h((string) $tarjeta['principal']) ?></strong>
                                <small>Sopa: <?= $h((string) $tarjeta['sopa']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section>
            <div class="seccion-titulo"><h2>Accesos rápidos</h2></div>
            <div class="accesos">
                <?php foreach ($modulos as $i => $modulo): ?>
                    <a class="acceso <?= $colores[$i % count($colores)] ?>" href="<?= $h($modulo['ruta']) ?>">
                        <span class="acceso-icono">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><?= $iconos[$modulo['titulo']] ?? '<circle cx="12" cy="12" r="8"/>' ?></svg>
                        </span>
                        <strong><?= $h($modulo['titulo']) ?></strong>
                        <span><?= $h($modulo['descripcion']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>