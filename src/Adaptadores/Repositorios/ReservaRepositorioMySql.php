<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use PDOException;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class ReservaRepositorioMySql implements ReservaRepositorio
{
    private const COLUMNAS = 'idReserva, idPersona, idUsuario, idPlato, fecha, modalidad, tarifa, estado, codigoQR, horaEntrega';

    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $idReserva): ?Reserva
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM reserva WHERE idReserva = :idReserva');
        $consulta->execute(['idReserva' => $idReserva]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    public function buscarPorCodigoQR(string $codigo): ?Reserva
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM reserva WHERE codigoQR = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    public function buscarActivaDePersonaEnFecha(int $idPersona, DateTimeImmutable $fecha): ?Reserva
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM reserva
             WHERE idPersona = :idPersona AND fecha = :fecha AND estado <> :cancelada'
        );
        $consulta->execute([
            'idPersona' => $idPersona,
            'fecha' => $fecha->format('Y-m-d'),
            'cancelada' => EstadoReserva::Cancelada->value,
        ]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    public function listarPorTitularEntre(int $idUsuario, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return $this->listarEntre($desde, $hasta, $idUsuario);
    }

    public function listarEntre(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $idUsuario = null): array
    {
        $sql = 'SELECT ' . self::COLUMNAS . ' FROM reserva WHERE fecha >= :desde AND fecha <= :hasta';
        $parametros = ['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')];
        if ($idUsuario !== null) {
            $sql .= ' AND idUsuario = :idUsuario';
            $parametros['idUsuario'] = $idUsuario;
        }
        $consulta = $this->pdo->prepare($sql . ' ORDER BY fecha, idReserva');
        $consulta->execute($parametros);
        return array_map(fn (array $fila) => $this->aEntidad($fila), $consulta->fetchAll());
    }

    public function listarConfirmadasDePersonaDesde(int $idPersona, DateTimeImmutable $desde): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM reserva
             WHERE idPersona = :idPersona AND fecha >= :desde AND estado = :confirmada ORDER BY fecha'
        );
        $consulta->execute([
            'idPersona' => $idPersona,
            'desde' => $desde->format('Y-m-d'),
            'confirmada' => EstadoReserva::Confirmada->value,
        ]);
        return array_map(fn (array $fila) => $this->aEntidad($fila), $consulta->fetchAll());
    }

    public function guardar(Reserva $reserva): int
    {
        $datos = [
            'idPersona' => $reserva->getIdPersona(),
            'idUsuario' => $reserva->getIdUsuario(),
            'idPlato' => $reserva->getIdPlato(),
            'fecha' => $reserva->getFecha()->format('Y-m-d'),
            'modalidad' => $reserva->getModalidad()->value,
            'tarifa' => $reserva->getTarifa(),
            'estado' => $reserva->getEstado()->value,
            'codigoQR' => $reserva->getCodigoQR(),
            'horaEntrega' => $reserva->getHoraEntrega()?->format('Y-m-d H:i:s'),
        ];

        try {
            if ($reserva->getIdReserva() === 0) {
                $this->pdo->prepare(
                    'INSERT INTO reserva (idPersona, idUsuario, idPlato, fecha, modalidad, tarifa, estado, codigoQR, horaEntrega)
                     VALUES (:idPersona, :idUsuario, :idPlato, :fecha, :modalidad, :tarifa, :estado, :codigoQR, :horaEntrega)'
                )->execute($datos);
                return (int) $this->pdo->lastInsertId();
            }

            $datos['idReserva'] = $reserva->getIdReserva();
            $this->pdo->prepare(
                'UPDATE reserva SET idPersona = :idPersona, idUsuario = :idUsuario, idPlato = :idPlato,
                        fecha = :fecha, modalidad = :modalidad, tarifa = :tarifa, estado = :estado,
                        codigoQR = :codigoQR, horaEntrega = :horaEntrega
                 WHERE idReserva = :idReserva'
            )->execute($datos);
            return $reserva->getIdReserva();
        } catch (PDOException $error) {
            // Segunda barrera: el código QR es único en la base de datos.
            if ($error->getCode() === '23000') {
                throw new ReglaDeNegocioException('No se pudo registrar la reserva. Inténtalo de nuevo.');
            }
            throw $error;
        }
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

    /** @param array<string, mixed> $fila */
    private function aEntidad(array $fila): Reserva
    {
        return new Reserva(
            (int) $fila['idReserva'],
            (int) $fila['idPersona'],
            (int) $fila['idUsuario'],
            (int) $fila['idPlato'],
            new DateTimeImmutable($fila['fecha']),
            Modalidad::from($fila['modalidad']),
            (float) $fila['tarifa'],
            EstadoReserva::from($fila['estado']),
            (string) $fila['codigoQR'],
            $fila['horaEntrega'] === null ? null : new DateTimeImmutable($fila['horaEntrega']),
        );
    }
}