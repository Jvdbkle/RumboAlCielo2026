<?php
/**
 * Banner del menú semanal con el selector de semana. Lo usan la vista del administrador y la
 * del empleado.
 *
 * @var string $tituloBanner
 * @var DateTimeImmutable $lunes Lunes de la semana que se muestra.
 * @var DateTimeImmutable $anterior
 * @var DateTimeImmutable $siguiente
 * @var string $ruta Ruta de la página, por ejemplo "/administrador/menu".
 * @var array{texto: string, clase: string}|null $estadoSemana Etiqueta opcional (Publicado, etc.).
 */
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Reloj;

$mesesCortos = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$mesesLargos = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$jueves = $lunes->modify('+3 day');

// Cuántas semanas hay entre la semana mostrada y la actual (0 = esta semana).
$lunesActual = Menu::lunesDeLaSemana(Reloj::ahora());
$diferencia = (int) round(((int) $lunes->format('U') - (int) $lunesActual->format('U')) / (7 * 86400));
if ($diferencia === 0) {
    $cuando = 'Esta semana';
} elseif ($diferencia === 1) {
    $cuando = 'Próxima semana';
} elseif ($diferencia === -1) {
    $cuando = 'Semana pasada';
} elseif ($diferencia > 1) {
    $cuando = 'En ' . $diferencia . ' semanas';
} else {
    $cuando = 'Hace ' . abs($diferencia) . ' semanas';
}

if ($lunes->format('n') === $jueves->format('n')) {
    $rango = $lunes->format('j') . ' – ' . $jueves->format('j') . ' ' . $mesesCortos[(int) $jueves->format('n')];
    $rangoLargo = 'Semana del ' . $lunes->format('j') . ' al ' . $jueves->format('j') . ' de ' . $mesesLargos[(int) $jueves->format('n')];
} else {
    $rango = $lunes->format('j') . ' ' . $mesesCortos[(int) $lunes->format('n')] . ' – ' . $jueves->format('j') . ' ' . $mesesCortos[(int) $jueves->format('n')];
    $rangoLargo = 'Semana del ' . $lunes->format('j') . ' de ' . $mesesLargos[(int) $lunes->format('n')] . ' al ' . $jueves->format('j') . ' de ' . $mesesLargos[(int) $jueves->format('n')];
}
$rangoLargo .= ' de ' . $jueves->format('Y');
?>
        <header class="banner-menu">
            <div class="banner-texto">
                <span class="banner-sobre">Almuerzos de la semana</span>
                <h1><?= htmlspecialchars($tituloBanner, ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= $rangoLargo ?></p>
            </div>

            <div class="banner-controles">
                <nav class="selector-semana" aria-label="Cambiar de semana">
                    <a class="flecha" href="<?= $ruta ?>?semana=<?= $anterior->format('Y-m-d') ?>" aria-label="Semana anterior" title="Semana anterior">&#8249;</a>
                    <div class="rango">
                        <strong><?= $rango ?></strong>
                        <span><?= $cuando ?></span>
                    </div>
                    <a class="flecha" href="<?= $ruta ?>?semana=<?= $siguiente->format('Y-m-d') ?>" aria-label="Semana siguiente" title="Semana siguiente">&#8250;</a>
                </nav>
                <div class="banner-extras">
                    <?php if ($diferencia !== 0): ?>
                        <a class="ir-hoy" href="<?= $ruta ?>">Ir a esta semana</a>
                    <?php endif; ?>
                    <?php if (!empty($estadoSemana)): ?>
                        <span class="etiqueta <?= $estadoSemana['clase'] ?>"><?= htmlspecialchars($estadoSemana['texto'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>