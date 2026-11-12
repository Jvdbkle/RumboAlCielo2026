<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum EstadoUsuario: string
{
    case Habilitado = 'Habilitado';
    case Deshabilitado = 'Deshabilitado';
}