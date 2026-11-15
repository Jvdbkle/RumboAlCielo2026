<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\LiquidacionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;

/** HU-26 criterio 4: muestra la liquidación del mes con el total a descontar a cada empleado titular. */
final class ConsultarLiquidacion
{
    public function __construct(
        private LiquidacionRepositorio $liquidaciones,
        private PersonaRepositorio $personas,
        private UsuarioRepositorio $usuarios,
    ) {
    }

    /** @return ResultadoLiquidacion|null null si el mes todavía no se liquidó. */
    public function ejecutar(DateTimeImmutable $mes): ?ResultadoLiquidacion
    {
        $liquidacion = $this->liquidaciones->buscarPorMes($mes);
        if ($liquidacion === null) {
            return null;
        }

        $filas = [];
        $totales = [];
        foreach ($liquidacion->getDetalles() as $detalle) {
            $nombreTitular = $this->nombreDeUsuario($detalle->getIdUsuario());
            $filas[] = new FilaLiquidacion(
                $this->nombreDePersona($detalle->getIdPersona()),
                $detalle->getIdUsuario(),
                $nombreTitular,
                $detalle->getModalidad(),
                $detalle->getCantidad(),
                $detalle->getPenalidad(),
                $detalle->getTotal(),
            );
            $totales[$detalle->getIdUsuario()] ??= ['nombre' => $nombreTitular, 'total' => 0.0];
            $totales[$detalle->getIdUsuario()]['total'] += $detalle->getTotal();
        }

        return new ResultadoLiquidacion($liquidacion->getMes(), $liquidacion->getEstado(), $filas, $totales, $liquidacion->totalGeneral());
    }

    private function nombreDePersona(int $idPersona): string
    {
        $persona = $this->personas->buscarPorId($idPersona);
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }

    private function nombreDeUsuario(int $idUsuario): string
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        return $usuario === null ? '' : $this->nombreDePersona($usuario->getIdPersona());
    }
}