<?php
/**
 * @var string $titulo  Título del panel.
 * @var string $mensaje Texto de bienvenida.
 * @var array<int, array{titulo: string, descripcion: string, ruta: string}> $modulos Accesos del rol.
 */
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="ayuda izquierda"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>

        <div class="modulos">
            <?php foreach ($modulos as $modulo): ?>
                <a class="modulo" href="<?= htmlspecialchars($modulo['ruta'], ENT_QUOTES, 'UTF-8') ?>">
                    <strong><?= htmlspecialchars($modulo['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>