<?php
/**
 * Cabecera común de las pantallas de gestión: enlaces para volver, banner con el título y,
 * si hay, el botón de la acción principal.
 *
 * @var array<int, array{0: string, 1: string}> $enlacesVolver Pares [texto, ruta].
 * @var string $sobreTitulo Texto pequeño sobre el título.
 * @var string $tituloPagina
 * @var string $subtituloPagina
 * @var array{texto: string, href: string, ventana: bool}|null $accionPagina
 */
require __DIR__ . '/volver.php';
?>
        <header class="banner-menu">
            <div class="banner-texto">
                <span class="banner-sobre"><?= htmlspecialchars($sobreTitulo, ENT_QUOTES, 'UTF-8') ?></span>
                <h1><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($subtituloPagina, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php if (!empty($accionPagina)): ?>
                <div class="banner-controles">
                    <a class="boton-banner" <?= $accionPagina['ventana'] ? 'data-ventana ' : '' ?>href="<?= htmlspecialchars($accionPagina['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($accionPagina['texto'], ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php endif; ?>
        </header>