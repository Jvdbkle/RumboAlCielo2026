<?php
/** @var string $error Mensaje de error para mostrar (vacío si no hay). */
/** @var string $correo Correo escrito antes, para no obligar a escribirlo de nuevo. */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumbo al Cielo - Iniciar sesión</title>
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
        <form class="tarjeta" method="post" action="/login">
                        <?= \RumboAlCielo\Infraestructura\Seguridad\TokenCsrf::campo() ?>
            <div class="icono">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/></svg>
            </div>
            <h1>Iniciar sesión</h1>
            <p class="ayuda">Ingresa con tu correo y tu contraseña.</p>

            <?php if ($error !== ''): ?>
                <div class="alerta" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>" required autofocus>

            <label for="contrasenia">Contraseña</label>
            <input type="password" id="contrasenia" name="contrasenia" required>

            <button type="submit">Iniciar Sesión</button>
        </form>
        <p class="pie">Sistema de reserva de almuerzos</p>
    </main>
</body>
</html>