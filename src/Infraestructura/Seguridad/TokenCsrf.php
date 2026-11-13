<?php

declare(strict_types=1);

namespace RumboAlCielo\Infraestructura\Seguridad;

/**
 * Protección contra CSRF (peticiones falsas desde otro sitio). Cada formulario que cambia
 * datos lleva un token secreto guardado en la sesión del usuario; si el token no coincide,
 * la petición se rechaza.
 */
final class TokenCsrf
{
    private const CLAVE = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::CLAVE]) || !is_string($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CLAVE];
    }

    /** El campo oculto que debe ir dentro de cada formulario POST. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function esValido(?string $enviado): bool
    {
        return $enviado !== null && hash_equals(self::token(), $enviado);
    }
}