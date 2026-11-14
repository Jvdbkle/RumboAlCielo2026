<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-18: el empleado cancela la reserva de un almuerzo propio o de un familiar, siempre
 * antes de las 09:00 AM del día del almuerzo. Desde esa hora nadie puede cancelar (ni
 * siquiera el administrador) y el almuerzo se cobra.
 */
final class CancelarReserva
{
    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
    ) {
    }

    /**
     * @param int|null $idTitular El empleado que cancela: solo puede cancelar reservas a su cargo.
     *                            Con null (administrador) no se comprueba el titular.
     * @return string El nombre de la persona cuya reserva se canceló.
     */
    public function ejecutar(int $idReserva, ?int $idTitular): string
    {
        $reserva = $this->reservas->buscarPorId($idReserva);
        if ($reserva === null || ($idTitular !== null && $reserva->getIdUsuario() !== $idTitular)) {
            throw new ReglaDeNegocioException('No se encontró la reserva.');
        }

        // Criterios 1 y 2: la entidad aplica el estado y la hora de corte.
        $reserva->cancelar();
        $this->reservas->guardar($reserva);

        $persona = $this->personas->buscarPorId($reserva->getIdPersona());
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }
}