<?php
/**
 * HU-25: exportar las reservas de un rango de fechas a CSV o Excel.
 *
 * @var DateTimeImmutable|null $desde
 * @var DateTimeImmutable|null $hasta
 * @var string $formato
 * @var string $error
 */
$titulo = 'Exportar reservas';
$enlacesVolver = [['Volver al panel', '/administrador']];
$sobreTitulo = 'Reportes';
$tituloPagina = 'Exportar reservas';
$subtituloPagina = 'Descarga las reservas de un rango de fechas para revisarlas.';
$accionPagina = null;

$h = fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <?php require __DIR__ . '/partes/cabecera_pagina.php'; ?>

        <?php if ($error !== ''): ?>
            <div class="aviso error" role="alert"><?= $h($error) ?></div>
        <?php endif; ?>

        <form class="filtros" method="get" action="/administrador/reservas/exportar">
            <input type="hidden" name="exportar" value="1">
            <div>
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" value="<?= $desde?->format('Y-m-d') ?>">
            </div>
            <div>
                <label for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?= $hasta?->format('Y-m-d') ?>">
            </div>
            <div>
                <label for="formato">Formato</label>
                <select id="formato" name="formato">
                    <option value="csv" <?= $formato === 'csv' ? 'selected' : '' ?>>CSV</option>
                    <option value="xlsx" <?= $formato === 'xlsx' ? 'selected' : '' ?>>Excel (.xlsx)</option>
                </select>
            </div>
            <button type="submit">Exportar</button>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>