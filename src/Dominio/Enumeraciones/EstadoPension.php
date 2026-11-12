<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum EstadoPension: string
{
    case Pendiente = 'Pendiente';
    case Activa = 'Activa';
    case Rechazada = 'Rechazada';
}