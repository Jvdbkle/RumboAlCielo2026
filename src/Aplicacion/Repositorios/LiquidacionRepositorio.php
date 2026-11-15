<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Entidades\Liquidacion;

interface LiquidacionRepositorio
{
    /** La liquidación (con sus detalles) del mes de esa fecha, o null si aún no se generó. */
    public function buscarPorMes(DateTimeImmutable $mes): ?Liquidacion;

    /** Guarda la liquidación con sus detalles; los detalles anteriores se reemplazan. Devuelve su id. */
    public function guardar(Liquidacion $liquidacion): int;
}