<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\LiquidacionRepositorio;
use RumboAlCielo\Dominio\Entidades\Liquidacion;

/** Repositorio falso para las pruebas: guarda las liquidaciones en memoria, sin base de datos. */
final class LiquidacionRepositorioEnMemoria implements LiquidacionRepositorio
{
    /** @var array<string, Liquidacion> */
    private array $porMes = [];
    private int $siguienteId = 1;

    public function buscarPorMes(DateTimeImmutable $mes): ?Liquidacion
    {
        return $this->porMes[$mes->format('Y-m')] ?? null;
    }

    public function guardar(Liquidacion $liquidacion): int
    {
        $id = $liquidacion->getIdLiquidacion() ?: $this->siguienteId++;
        // Se guarda una copia con su id, como haría la base de datos.
        $copia = new Liquidacion($id, $liquidacion->getMes(), $liquidacion->getEstado());
        foreach ($liquidacion->getDetalles() as $detalle) {
            $copia->agregarDetalle($detalle);
        }
        $this->porMes[$liquidacion->getMes()->format('Y-m')] = $copia;
        return $id;
    }
}