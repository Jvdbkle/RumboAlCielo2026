<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-21: el empleado ve el código QR de sus reservas confirmadas de hoy, uno por persona,
 * para que él y sus familiares retiren el almuerzo en momentos distintos.
 */
final class ConsultarMisCodigosQR
{
    public const SIN_RESERVA_HOY = 'No cuentas con una reserva para hoy.';

    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
    ) {
    }

    /**
     * Criterios 1 y 2: un código por cada reserva confirmada de hoy, a cargo del empleado.
     * Criterio 3: sin reservas devuelve una lista vacía y la pantalla muestra SIN_RESERVA_HOY.
     *
     * @return FilaCodigoQR[]
     */
    public function ejecutar(int $idEmpleado): array
    {
        $hoy = Reloj::ahora()->setTime(0, 0);

        $filas = [];
        foreach ($this->reservas->listarPorTitularEntre($idEmpleado, $hoy, $hoy) as $reserva) {
            if ($reserva->getEstado() !== EstadoReserva::Confirmada) {
                continue;
            }
            $persona = $this->personas->buscarPorId($reserva->getIdPersona());
            $filas[] = new FilaCodigoQR(
                $reserva->getIdReserva(),
                $persona === null ? '' : $persona->obtenerNombreCompleto(),
                $reserva->getCodigoQR(),
                $reserva->getTarifa(),
            );
        }
        return $filas;
    }
}