<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-28: el administrador consulta las reservas de una fecha o de un rango de fechas, con el
 * nombre de la persona, su empleado titular y el estado (confirmada o entregada). Puede
 * filtrar por un empleado para ver solo sus reservas y las de sus familiares.
 */
final class ConsultarReservasAdmin
{
    public const SIN_RESULTADOS = 'No se encontraron reservas para la fecha o el filtro elegido.';

    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
        private UsuarioRepositorio $usuarios,
    ) {
    }

    /**
     * @param DateTimeImmutable|null $hasta Fin del rango; si es null se consulta solo la fecha "desde".
     * @param int|null $idEmpleado Criterio 2: solo las reservas de ese empleado y de sus familiares.
     * @return FilaReservaAdmin[] Criterio 3: una lista vacía significa que no hay reservas.
     */
    public function ejecutar(DateTimeImmutable $desde, ?DateTimeImmutable $hasta = null, ?int $idEmpleado = null): array
    {
        $desde = $desde->setTime(0, 0);
        $hasta = ($hasta ?? $desde)->setTime(0, 0);
        if ($hasta < $desde) {
            throw new ReglaDeNegocioException('La fecha final no puede ser anterior a la fecha inicial.');
        }

        $nombres = [];
        $filas = [];
        foreach ($this->reservas->listarEntre($desde, $hasta, $idEmpleado) as $reserva) {
            // Criterio 1: se muestran las confirmadas y las entregadas; las canceladas no.
            if ($reserva->getEstado() === EstadoReserva::Cancelada) {
                continue;
            }
            $filas[] = new FilaReservaAdmin(
                $reserva->getIdReserva(),
                $reserva->getFecha(),
                $nombres['p' . $reserva->getIdPersona()] ??= $this->nombreDePersona($reserva->getIdPersona()),
                $nombres['u' . $reserva->getIdUsuario()] ??= $this->nombreDeUsuario($reserva->getIdUsuario()),
                $reserva->getEstado(),
                $reserva->getModalidad(),
                $reserva->getTarifa(),
            );
        }
        return $filas;
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