<?php
/**
 * HU-03 criterio 3: lista de usuarios con su estado. Desde aquí se registra, edita,
 * deshabilita y reincorpora (HU-03, HU-06) y se restablece la contraseña (HU-02).
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\FilaUsuario[] $filas
 * @var array{tipo: string, texto: string}|null $aviso
 * @var int|null $idActual Id del administrador que tiene la sesión abierta.
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Usuarios';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <div class="encabezado-pagina">
            <div>
                <h1>Usuarios</h1>
                <p class="ayuda izquierda">Usuarios registrados en el sistema y su estado.</p>
            </div>
            <a class="boton" href="/administrador/usuarios/nuevo">Registrar usuario</a>
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
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($filas === []): ?>
                    <tr><td colspan="5">Todavía no hay usuarios registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($filas as $fila): ?>
                    <?php $habilitado = $fila->estado === 'Habilitado'; ?>
                    <tr>
                        <td><?= htmlspecialchars($fila->nombreCompleto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($fila->correo, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($fila->rol, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="etiqueta <?= $habilitado ? 'ok' : 'baja' ?>">
                                <?= htmlspecialchars($fila->estado, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a class="boton chico secundario" href="/administrador/usuarios/editar?id=<?= $fila->idUsuario ?>">Editar</a>

                                
                                <?php if ($fila->rol !== 'Administrador'): ?>
                                    <a class="boton chico secundario" href="/administrador/familiares?empleado=<?= $fila->idUsuario ?>">Familiares</a>
                                <?php endif; ?>
                                
                                <?php if ($habilitado && $fila->idUsuario !== $idActual): ?>
                                    <form method="post" action="/administrador/usuarios/deshabilitar">
                                        <?= TokenCsrf::campo() ?>
                                        <input type="hidden" name="idUsuario" value="<?= $fila->idUsuario ?>">
                                        <button type="submit" class="chico peligro">Deshabilitar</button>
                                    </form>
                                <?php elseif (!$habilitado): ?>
                                    <form method="post" action="/administrador/usuarios/reincorporar">
                                        <?= TokenCsrf::campo() ?>
                                        <input type="hidden" name="idUsuario" value="<?= $fila->idUsuario ?>">
                                        <button type="submit" class="chico">Reincorporar</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="/administrador/usuarios/restablecer">
                                    <?= TokenCsrf::campo() ?>
                                    <input type="hidden" name="idUsuario" value="<?= $fila->idUsuario ?>">
                                    <button type="submit" class="chico secundario">Restablecer contraseña</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>