<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Enumeraciones;

enum EstadoLiquidacion: string
{
    case Generada = 'Generada';
    case Cerrada = 'Cerrada';
}