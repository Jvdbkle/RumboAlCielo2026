<?php
/**
 * HU-15: días sin atención registrados. Desde aquí se registra uno nuevo.
 *
 * @var \RumboAlCielo\Dominio\Entidades\Feriado[] $feriados
 * @var array{tipo: string, texto: string}|null $aviso
 */
$titulo = 'Feriados';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <div class="encabezado-pagina">
            <div>
                <h1>Feriados y días sin atención</h1>
                <p class="ayuda izquierda">En estas fechas no hay menú ni se pueden hacer reservas.</p>
            </div>
            <a class="boton" data-ventana href="/administrador/feriados/nuevo">Registrar día sin atención</a>
        </div>

        <?php if ($aviso !== null): ?>
            <div class="aviso <?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status">
                <?= htmlspecialchars($aviso['texto'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($feriados === []): ?>
                    <tr><td colspan="2">Todavía no hay días sin atención registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($feriados as $feriado): ?>
                    <tr>
                        <td><?= $feriado->getFecha()->format('d/m/Y') ?></td>
                        <td><?= htmlspecialchars($feriado->getMotivo(), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>