<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum EstadoFamiliar: string
{
    case Activo = 'Activo';
    case Eliminado = 'Eliminado';
}