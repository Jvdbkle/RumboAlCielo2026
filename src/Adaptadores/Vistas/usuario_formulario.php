<?php
/**
 * HU-03 criterios 1 y 4: formulario para registrar o editar un usuario.
 *
 * @var string $titulo
 * @var string $accion Ruta a la que se envía el formulario.
 * @var int|null $idUsuario Id del usuario que se edita; null al registrar uno nuevo.
 * @var array<string, string> $valores Datos que se muestran en los campos.
 * @var \RumboAlCielo\Dominio\Entidades\Rol[] $roles
 * @var string $error Mensaje de error (vacío si no hay).
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

require __DIR__ . '/partes/encabezado.php';
$esEdicion = $idUsuario !== null;
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="<?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>" novalidate>
            <?= TokenCsrf::campo() ?>
            <?php if ($esEdicion): ?>
                <input type="hidden" name="idUsuario" value="<?= (int) $idUsuario ?>">
            <?php endif; ?>

            <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="ayuda">
                <?= $esEdicion
                    ? 'Modifica los datos del usuario y guarda los cambios.'
                    : 'El correo será su usuario de ingreso y el teléfono su primera contraseña.' ?>
            </p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="nombres">Nombres</label>
            <input type="text" id="nombres" name="nombres" maxlength="100" required autofocus
                   value="<?= htmlspecialchars($valores['nombres'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="apellidos">Apellidos</label>
            <input type="text" id="apellidos" name="apellidos" maxlength="100" required
                   value="<?= htmlspecialchars($valores['apellidos'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" maxlength="150" required
                   value="<?= htmlspecialchars($valores['correo'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" maxlength="8" inputmode="numeric" pattern="[0-9]{8}"
                   title="8 dígitos, solo números" required
                   value="<?= htmlspecialchars($valores['telefono'], ENT_QUOTES, 'UTF-8') ?>">

            <?php if (!$esEdicion): ?>
                <label for="idRol">Rol</label>
                <select id="idRol" name="idRol" required>
                    <option value="">Selecciona un rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol->getIdRol() ?>" <?= (string) $rol->getIdRol() === $valores['idRol'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rol->getNombreRol(), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <div class="botones">
                <button type="submit">Guardar</button>
                <a class="boton secundario" href="/administrador/usuarios">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>