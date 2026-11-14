<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;

final class ReservaRepositorioMySql implements ReservaRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function contarReservadasEnMes(int $idPersona, DateTimeImmutable $mes): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) FROM reserva
             WHERE idPersona = :idPersona AND fecha >= :inicio AND fecha < :fin AND estado <> :cancelada'
        );
        $consulta->execute($this->rangoDelMes($idPersona, $mes) + ['cancelada' => EstadoReserva::Cancelada->value]);
        return (int) $consulta->fetchColumn();
    }

    public function contarEntregadasEnMes(int $idPersona, DateTimeImmutable $mes): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) FROM reserva
             WHERE idPersona = :idPersona AND fecha >= :inicio AND fecha < :fin AND estado = :entregada'
        );
        $consulta->execute($this->rangoDelMes($idPersona, $mes) + ['entregada' => EstadoReserva::Entregada->value]);
        return (int) $consulta->fetchColumn();
    }

    public function pasarAModalidadDiariaEnMes(int $idPersona, DateTimeImmutable $mes): void
    {
        $consulta = $this->pdo->prepare(
            'UPDATE reserva SET modalidad = :modalidad, tarifa = :tarifa
             WHERE idPersona = :idPersona AND fecha >= :inicio AND fecha < :fin AND estado <> :cancelada'
        );
        $consulta->execute($this->rangoDelMes($idPersona, $mes) + [
            'modalidad' => Modalidad::Diaria->value,
            'tarifa' => Reserva::TARIFA_DIARIA,
            'cancelada' => EstadoReserva::Cancelada->value,
        ]);
    }

    /** @return array{idPersona: int, inicio: string, fin: string} */
    private function rangoDelMes(int $idPersona, DateTimeImmutable $mes): array
    {
        return [
            'idPersona' => $idPersona,
            'inicio' => $mes->modify('first day of this month')->format('Y-m-d'),
            'fin' => $mes->modify('first day of next month')->format('Y-m-d'),
        ];
    }
}