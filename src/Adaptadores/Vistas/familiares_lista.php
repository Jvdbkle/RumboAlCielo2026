<?php
/**
 * HU-04 criterio 2 y HU-07: familiares agregados a un empleado. Desde aquí se registra,
 * edita y elimina.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\DatosUsuario $empleado
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaFamiliar[] $filas
 * @var array{tipo: string, texto: string}|null $aviso
 */
$titulo = 'Familiares';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <div class="encabezado-pagina">
            <div>
                <h1>Familiares</h1>
                <p class="ayuda izquierda">
                    Empleado: <?= htmlspecialchars($empleado->nombres . ' ' . $empleado->apellidos, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="acciones">
                <a class="boton secundario" href="/administrador/usuarios">Volver a usuarios</a>
               <a class="boton" data-ventana href="/administrador/familiares/nuevo?empleado=<?= $empleado->idUsuario ?>">Registrar familiar</a>
            </div>
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
                        <th>Nombre</th>
                        <th>Parentesco</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($filas === []): ?>
                    <tr><td colspan="3">Este empleado todavía no tiene familiares registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila->nombreCompleto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($fila->parentesco, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton" data-ventana href="/administrador/familiares/nuevo?empleado=<?= $empleado->idUsuario ?>">Registrar familiar</a>
                                <a class="boton chico peligro" data-ventana href="/administrador/familiares/eliminar?id=<?= $fila->idFamiliar ?>">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>