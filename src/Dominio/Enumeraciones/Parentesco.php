<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum Parentesco: string
{
    case Conyuge = 'conyuge';
    case Hijo = 'hijo';
    case Hija = 'hija';
}