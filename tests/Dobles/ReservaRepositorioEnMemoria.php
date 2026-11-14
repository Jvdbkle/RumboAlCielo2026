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
    /** @var Reserva[] */
    private array $reservas = [];

    /** Agrega una reserva ya armada, para preparar la situación de una prueba. */
    public function agregar(Reserva $reserva): void
    {
        $this->reservas[] = $reserva;
    }

    /** @return Reserva[] */
    public function todas(): array
    {
        return $this->reservas;
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