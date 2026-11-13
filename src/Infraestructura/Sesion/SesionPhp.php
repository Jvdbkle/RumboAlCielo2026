<?php

declare(strict_types=1);

namespace RumboAlCielo\Infraestructura\Sesion;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Puertos\Sesion;

/**
 * Implementación real del contrato Sesion con la sesión nativa de PHP ($_SESSION).
 * Requiere que session_start() ya se haya llamado (se hace en public/index.php).
 */
final class SesionPhp implements Sesion
{
    public function iniciar(int $idUsuario, int $idRol, DateTimeImmutable $ahora): void
    {
        // Renovar el identificador al ingresar evita la fijación de sesión.
        session_regenerate_id(true);
        $_SESSION['idUsuario'] = $idUsuario;
        $_SESSION['idRol'] = $idRol;
        $_SESSION['ultimaActividad'] = $ahora->getTimestamp();
    }

    public function idUsuario(): ?int
    {
        return isset($_SESSION['idUsuario']) ? (int) $_SESSION['idUsuario'] : null;
    }

    public function idRol(): ?int
    {
        return isset($_SESSION['idRol']) ? (int) $_SESSION['idRol'] : null;
    }

    public function ultimaActividad(): ?DateTimeImmutable
    {
        if (!isset($_SESSION['ultimaActividad'])) {
            return null;
        }
        return (new DateTimeImmutable())->setTimestamp((int) $_SESSION['ultimaActividad']);
    }

    public function registrarActividad(DateTimeImmutable $ahora): void
    {
        $_SESSION['ultimaActividad'] = $ahora->getTimestamp();
    }

    public function destruir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}