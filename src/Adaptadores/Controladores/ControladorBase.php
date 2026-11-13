<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Infraestructura\Seguridad\TokenCsrf;

/** Ayudas comunes de los controladores: mostrar una vista, redirigir y proteger formularios. */
abstract class ControladorBase
{
    /** @param array<string, mixed> $datos Variables que la vista podrá usar. */
    protected function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require dirname(__DIR__) . '/Vistas/' . $vista . '.php';
    }

    protected function redirigir(string $ruta): never
    {
        header('Location: ' . $ruta);
        exit;
    }
    /** Deja un mensaje para mostrarlo en la próxima pantalla (por ejemplo, tras guardar). */
    protected function avisar(string $tipo, string $texto): void
    {
        $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    /** @return array{tipo: string, texto: string}|null El mensaje pendiente; se muestra una sola vez. */
    protected function tomarAviso(): ?array
    {
        $aviso = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);
        return is_array($aviso) ? $aviso : null;
    }
    /** Todo formulario que cambia datos debe llamar a esto antes de actuar (protección CSRF). */
    protected function exigirTokenValido(): void
    {
        if (!TokenCsrf::esValido(isset($_POST['_token']) ? (string) $_POST['_token'] : null)) {
            http_response_code(403);
            echo 'Solicitud no válida. Vuelve a la página anterior, recárgala e inténtalo de nuevo.';
            exit;
        }
    }
}