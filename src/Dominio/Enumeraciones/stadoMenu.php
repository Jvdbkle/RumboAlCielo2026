<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum EstadoMenu: string
{
    case Pendiente = 'Pendiente';
    case Publicado = 'Publicado';
}