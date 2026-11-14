<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;

/** Repositorio falso para las pruebas: guarda las reservas en memoria, sin base de datos. */
final class ReservaRepositorioEnMemoria implements ReservaRepositorio
{
    /** @var array<int, Reserva> */
    private array $reservas = [];
    private int $siguienteId = 1000;

    /** Agrega una reserva ya armada, para preparar la situación de una prueba. */
    public function agregar(Reserva $reserva): void
    {
        $this->reservas[] = $reserva;
    }

    /** @return Reserva[] */
    public function todas(): array
    {
        return array_values($this->reservas);
    }

    public function buscarPorId(int $idReserva): ?Reserva
    {
        foreach ($this->reservas as $reserva) {
            if ($reserva->getIdReserva() === $idReserva) {
                return $reserva;
            }
        }
        return null;
    }

    public function buscarPorCodigoQR(string $codigo): ?Reserva
    {
        foreach ($this->reservas as $reserva) {
            if ($reserva->getCodigoQR() !== '' && hash_equals($reserva->getCodigoQR(), $codigo)) {
                return $reserva;
            }
        }
        return null;
    }

    public function buscarActivaDePersonaEnFecha(int $idPersona, DateTimeImmutable $fecha): ?Reserva
    {
        foreach ($this->reservas as $reserva) {
            if ($reserva->getIdPersona() === $idPersona
                && $reserva->getFecha()->format('Y-m-d') === $fecha->format('Y-m-d')
                && $reserva->getEstado() !== EstadoReserva::Cancelada) {
                return $reserva;
            }
        }
        return null;
    }

    public function listarPorTitularEntre(int $idUsuario, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return $this->listarEntre($desde, $hasta, $idUsuario);
    }

    public function listarEntre(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $idUsuario = null): array
    {
        $lista = array_values(array_filter(
            $this->reservas,
            fn (Reserva $r) => $r->getFecha()->format('Y-m-d') >= $desde->format('Y-m-d')
                && $r->getFecha()->format('Y-m-d') <= $hasta->format('Y-m-d')
                && ($idUsuario === null || $r->getIdUsuario() === $idUsuario)
        ));
        usort($lista, fn (Reserva $a, Reserva $b) => [$a->getFecha(), $a->getIdReserva()] <=> [$b->getFecha(), $b->getIdReserva()]);
        return $lista;
    }

    public function listarConfirmadasDePersonaDesde(int $idPersona, DateTimeImmutable $desde): array
    {
        return array_values(array_filter(
            $this->reservas,
            fn (Reserva $r) => $r->getIdPersona() === $idPersona
                && $r->getEstado() === EstadoReserva::Confirmada
                && $r->getFecha()->format('Y-m-d') >= $desde->format('Y-m-d')
        ));
    }

    public function guardar(Reserva $reserva): int
    {
        if ($reserva->getIdReserva() !== 0) {
            return $reserva->getIdReserva();
        }

        // Igual que la base de datos: una reserva nueva (id 0) recibe el siguiente número.
        $id = $this->siguienteId++;
        $this->reservas[] = new Reserva(
            $id,
            $reserva->getIdPersona(),
            $reserva->getIdUsuario(),
            $reserva->getIdPlato(),
            $reserva->getFecha(),
            $reserva->getModalidad(),
            $reserva->getTarifa(),
            $reserva->getEstado(),
            $reserva->getCodigoQR(),
            $reserva->getHoraEntrega(),
        );
        return $id;
    }

    public function contarReservadasEnMes(int $idPersona, DateTimeImmutable $mes): int
    {
        return count(array_filter(
            $this->delMes($idPersona, $mes),
            fn (Reserva $r) => $r->getEstado() !== EstadoReserva::Cancelada
        ));
    }

    public function contarEntregadasEnMes(int $idPersona, DateTimeImmutable $mes): int
    {
        return count(array_filter(
            $this->delMes($idPersona, $mes),
            fn (Reserva $r) => $r->getEstado() === EstadoReserva::Entregada
        ));
    }

    public function pasarAModalidadDiariaEnMes(int $idPersona, DateTimeImmutable $mes): void
    {
        foreach ($this->delMes($idPersona, $mes) as $reserva) {
            if ($reserva->getEstado() !== EstadoReserva::Cancelada) {
                $reserva->volverADiaria();
            }
        }
    }

    /** @return Reserva[] */
    private function delMes(int $idPersona, DateTimeImmutable $mes): array
    {
        return array_filter(
            $this->reservas,
            fn (Reserva $r) => $r->getIdPersona() === $idPersona
                && $r->getFecha()->format('Y-m') === $mes->format('Y-m')
        );
    }
}