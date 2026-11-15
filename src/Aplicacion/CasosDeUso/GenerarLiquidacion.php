<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\LiquidacionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Entidades\DetalleLiquidacion;
use RumboAlCielo\Dominio\Entidades\Liquidacion;
use RumboAlCielo\Dominio\Enumeraciones\EstadoLiquidacion;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-26: el administrador genera la liquidación del mes. Cuenta los almuerzos cobrables de cada
 * persona (todo lo no cancelado, incluso quien reservó y no asistió) y deja el total a descontar
 * al empleado titular. El cálculo del importe (15 Bs, penalidad de 10 almuerzos, 18 Bs) vive en
 * DetalleLiquidacion. Una persona con pensión activa se liquida aunque no haya consumido.
 */
final class GenerarLiquidacion
{
    public function __construct(
        private ReservaRepositorio $reservas,
        private PensionRepositorio $pensiones,
        private LiquidacionRepositorio $liquidaciones,
    ) {
    }

    public function ejecutar(DateTimeImmutable $mes): Liquidacion
    {
        $inicio = $mes->modify('first day of this month')->setTime(0, 0);
        if ($inicio > Reloj::ahora()->modify('first day of this month')->setTime(0, 0)) {
            throw new ReglaDeNegocioException('No se puede liquidar un mes que todavía no comenzó.');
        }

        $existente = $this->liquidaciones->buscarPorMes($inicio);
        if ($existente !== null && $existente->getEstado() === EstadoLiquidacion::Cerrada) {
            throw new ReglaDeNegocioException('La liquidación del mes ya está cerrada.');
        }

        /** @var array<int, array{usuario: int, cantidad: int, modalidad: Modalidad}> $personas */
        $personas = [];
        foreach ($this->pensiones->listarDesde($inicio) as $pension) {
            if ($pension->getMes()->format('Y-m') === $inicio->format('Y-m') && $pension->estaActiva()) {
                $personas[$pension->getIdPersona()] = ['usuario' => $pension->getIdUsuario(), 'cantidad' => 0, 'modalidad' => Modalidad::Pensionada];
            }
        }
        foreach ($this->reservas->listarEntre($inicio, $inicio->modify('last day of this month')) as $reserva) {
            // Criterios 5 y 6: se cobra lo que no está cancelado; lo cancelado a tiempo no se cobra.
            if (!$reserva->esCobrable()) {
                continue;
            }
            $personas[$reserva->getIdPersona()] ??= ['usuario' => $reserva->getIdUsuario(), 'cantidad' => 0, 'modalidad' => $reserva->getModalidad()];
            $personas[$reserva->getIdPersona()]['cantidad']++;
        }
        if ($personas === []) {
            throw new ReglaDeNegocioException('No hay almuerzos para liquidar en ese mes.');
        }

        uksort($personas, fn (int $a, int $b) => [$personas[$a]['usuario'], $a] <=> [$personas[$b]['usuario'], $b]);

        $liquidacion = new Liquidacion($existente?->getIdLiquidacion() ?? 0, $inicio);
        foreach ($personas as $idPersona => $datos) {
            $liquidacion->agregarDetalle(new DetalleLiquidacion(0, 0, $idPersona, $datos['usuario'], $datos['modalidad'], $datos['cantidad']));
        }
        $liquidacion->generar();
        $this->liquidaciones->guardar($liquidacion);

        return $this->liquidaciones->buscarPorMes($inicio) ?? $liquidacion;
    }
}