<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Puertos\GeneradorHojaCalculo;
use RumboAlCielo\Aplicacion\Repositorios\LiquidacionRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-27: exporta la planilla mensual en Excel (columnas COD, NOM, MOD, CANT y TOT, una fila por
 * empleado titular y modalidad) solo si la liquidación del mes fue generada y cerrada.
 */
final class ExportarPlanilla
{
    public function __construct(
        private LiquidacionRepositorio $liquidaciones,
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
        private GeneradorHojaCalculo $hojas,
    ) {
    }

    public function ejecutar(DateTimeImmutable $mes): ArchivoExportado
    {
        $liquidacion = $this->liquidaciones->buscarPorMes($mes);
        // Criterio 2: sin liquidación generada y cerrada no se exporta.
        if ($liquidacion === null) {
            throw new ReglaDeNegocioException('Primero debe cerrarse el mes para exportar la planilla.');
        }
        $liquidacion->exigirCerrada();

        // Criterio 1: columnas COD, NOM, MOD, CANT y TOT.
        $filas = [];
        foreach ($liquidacion->consolidadoPorTitular() as $fila) {
            $usuario = $this->usuarios->buscarPorId($fila['idUsuario']);
            $persona = $usuario === null ? null : $this->personas->buscarPorId($usuario->getIdPersona());
            $filas[] = [
                $fila['idUsuario'],
                $persona === null ? '' : $persona->obtenerNombreCompleto(),
                $fila['modalidad']->value,
                $fila['cantidad'],
                round($fila['total'], 2),
            ];
        }

        return new ArchivoExportado(
            'planilla_' . $liquidacion->getMes()->format('Y-m') . '.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->hojas->generar('Planilla', ['COD', 'NOM', 'MOD', 'CANT', 'TOT'], $filas),
        );
    }
}