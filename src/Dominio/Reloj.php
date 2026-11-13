<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio;

use DateTimeImmutable;

/**
 * Fuente única de la hora actual. Las reglas del backlog dependen de la hora
 * (corte de las 09:00 AM, bloqueo de 5 minutos). Las pruebas fijan la hora con fijar().
 */
final class Reloj
{
    private static ?DateTimeImmutable $fija = null;

    public static function ahora(): DateTimeImmutable
    {
        return self::$fija ?? new DateTimeImmutable('now');
    }

    public static function fijar(?DateTimeImmutable $momento): void
    {
        self::$fija = $momento;
    }
}