<?php
/**
 * Parte común de las pantallas privadas: cabecera de la página y barra superior con el
 * botón "Cerrar sesión" (HU-05).
 *
 * @var string $titulo Título de la pantalla.
 */
use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;
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
        <a class="marca" href="/">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 8.5a4.5 4.5 0 0 1 .5 9H7z"/></svg>
            Rumbo al Cielo
        </a>
        <form method="post" action="/cerrar-sesion">
            <?= TokenCsrf::campo() ?>
            <button type="submit" class="salir">Cerrar sesión</button>
        </form>
    </header>