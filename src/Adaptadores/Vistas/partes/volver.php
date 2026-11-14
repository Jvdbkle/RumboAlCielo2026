<?php
/**
 * Enlaces para volver atrás (al panel, a la lista anterior).
 *
 * @var array<int, array{0: string, 1: string}> $enlacesVolver Pares [texto, ruta].
 */
?>
        <nav class="volver" aria-label="Volver">
            <?php foreach ($enlacesVolver as [$textoVolver, $rutaVolver]): ?>
                <a href="<?= htmlspecialchars($rutaVolver, ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&larr;</span> <?= htmlspecialchars($textoVolver, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </nav>