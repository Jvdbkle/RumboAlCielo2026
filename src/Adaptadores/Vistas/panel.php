<?php
/**
 * @var string $titulo  Título del panel.
 * @var string $mensaje Texto de bienvenida.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumbo al Cielo - <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
    <header class="barra">
        <span class="marca">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 8.5a4.5 4.5 0 0 1 .5 9H7z"/></svg>
            Rumbo al Cielo
        </span>
        <form method="post" action="/cerrar-sesion">
                        <?= \RumboAlCielo\Infraestructura\Seguridad\TokenCsrf::campo() ?>
            <button type="submit" class="salir">Cerrar sesión</button>
        </form>
    </header>

    <main class="centro">
        <section class="tarjeta">
            <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="ayuda"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        </section>
        <p class="pie">Sistema de reserva de almuerzos</p>
    </main>
</body>
</html>