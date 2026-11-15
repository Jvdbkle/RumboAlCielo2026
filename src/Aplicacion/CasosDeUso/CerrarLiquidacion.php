<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\LiquidacionRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-27 criterio 2: cerrar el mes es el paso previo a exportar la planilla. */
final class CerrarLiquidacion
{
    public function __construct(private LiquidacionRepositorio $liquidaciones)
    {
    }

    public function ejecutar(DateTimeImmutable $mes): void
    {
        $liquidacion = $this->liquidaciones->buscarPorMes($mes);
        if ($liquidacion === null) {
            throw new ReglaDeNegocioException('Primero debe generarse la liquidación del mes.');
        }
        $liquidacion->cerrar();
        $this->liquidaciones->guardar($liquidacion);
    }
}