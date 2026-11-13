<?php
/**
 * HU-07 criterios 1 y 3: confirmación antes de eliminar a un familiar.
 *
 * @var \RumboAlCielo\Aplicacion\CasosDeUso\DatosFamiliar $familiar
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

$titulo = 'Eliminar familiar';
require __DIR__ . '/partes/encabezado.php';
?>
    <main class="pagina">
        <form class="tarjeta ancha" method="post" action="/administrador/familiares/eliminar">
            <?= TokenCsrf::campo() ?>
            <input type="hidden" name="idFamiliar" value="<?= $familiar->idFamiliar ?>">
            <input type="hidden" name="confirmado" value="1">

            <h1>Eliminar familiar</h1>
            <p class="ayuda">
                ¿Seguro que quieres eliminar a
                <strong><?= htmlspecialchars($familiar->nombres . ' ' . $familiar->apellidos, ENT_QUOTES, 'UTF-8') ?></strong>?
                Ya no podrá seleccionarse para nuevas reservas.
            </p>

            <div class="botones">
                <button type="submit" class="peligro">Sí, eliminar</button>
                <a class="boton secundario" href="/administrador/familiares?empleado=<?= $familiar->idEmpleado ?>">Cancelar</a>
            </div>
        </form>
    </main>
<?php require __DIR__ . '/partes/pie.php'; ?>