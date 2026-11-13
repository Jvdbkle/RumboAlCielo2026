<?php
/**
 * HU-04 criterios 1 y 3: formulario para registrar o editar un familiar.
 *
 * @var string $titulo
 * @var string $accion Ruta a la que se envía el formulario.
 * @var int $idEmpleado Empleado al que pertenece el familiar.
 * @var int|null $idFamiliar Familiar que se edita; null al registrar uno nuevo.
 * @var array<string, string> $valores Datos que se muestran en los campos.
 * @var \RumboAlCielo\Dominio\Enumeraciones\Parentesco[] $parentescos
 * @var string $error Mensaje de error (vacío si no hay).
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

require __DIR__ . '/partes/encabezado.php';
$esEdicion = $idFamiliar !== null;
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="<?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>" novalidate>
            <?= TokenCsrf::campo() ?>
            <?php if ($esEdicion): ?>
                <input type="hidden" name="idFamiliar" value="<?= (int) $idFamiliar ?>">
            <?php else: ?>
                <input type="hidden" name="idEmpleado" value="<?= $idEmpleado ?>">
            <?php endif; ?>

            <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="ayuda">El parentesco puede ser cónyuge, hijo o hija.</p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="nombres">Nombres</label>
            <input type="text" id="nombres" name="nombres" maxlength="100" required autofocus
                   value="<?= htmlspecialchars($valores['nombres'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="apellidos">Apellidos</label>
            <input type="text" id="apellidos" name="apellidos" maxlength="100" required
                   value="<?= htmlspecialchars($valores['apellidos'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="parentesco">Parentesco</label>
            <select id="parentesco" name="parentesco" required>
                <option value="">Selecciona el parentesco</option>
                <?php foreach ($parentescos as $parentesco): ?>
                    <option value="<?= $parentesco->value ?>" <?= $parentesco->value === $valores['parentesco'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($parentesco->etiqueta(), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="botones">
                <button type="submit">Guardar</button>
                <a class="boton secundario" href="/administrador/familiares?empleado=<?= $idEmpleado ?>">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>