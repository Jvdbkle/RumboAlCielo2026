<?php
/** @var string $error Mensaje de error para mostrar (vacío si no hay). */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumbo al Cielo - Cambiar contraseña</title>
    <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
    <header class="barra">
        <span class="marca">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 8.5a4.5 4.5 0 0 1 .5 9H7z"/></svg>
            Rumbo al Cielo
        </span>
    </header>

    <main class="centro">
        <form class="tarjeta" method="post" action="/cambiar-contrasenia">
                        <?= \RumboAlCielo\Infraestructura\Seguridad\TokenCsrf::campo() ?>
            <div class="icono">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 8V7a3 3 0 0 1 6 0v3H9z"/></svg>
            </div>
            <h1>Cambiar contraseña</h1>
            <p class="ayuda">Es tu primer ingreso. Elige una contraseña nueva para continuar.</p>
            <p class="ayuda">Debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo especial (por ejemplo # o !), y ser distinta de tu teléfono.</p>
            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="nueva">Contraseña nueva</label>
            <input type="password" id="nueva" name="nueva" required minlength="8" autocomplete="new-password" autofocus>
            <button type="submit">Cambiar contraseña</button>
        </form>
        <p class="pie">Sistema de reserva de almuerzos</p>
    </main>
</body>
</html>