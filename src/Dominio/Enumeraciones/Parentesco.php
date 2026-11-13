<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum Parentesco: string
{
    case Conyuge = 'conyuge';
    case Hijo = 'hijo';
    case Hija = 'hija';

    /** Texto para mostrar en pantalla, con su tilde. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Conyuge => 'Cónyuge',
            self::Hijo => 'Hijo',
            self::Hija => 'Hija',
        };
    }
}