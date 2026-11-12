<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum EstadoReserva: string
{
    case Confirmada = 'Confirmada';
    case Entregada = 'Entregada';
    case Cancelada = 'Cancelada';
}