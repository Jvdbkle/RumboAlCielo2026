<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use PDOException;
use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class PensionRepositorioMySql implements PensionRepositorio
{
    private const COLUMNAS = 'idPension, idPersona, idUsuario, mes, tarifa, estado';

    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $idPension): ?Pension
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM pension WHERE idPension = :idPension');
        $consulta->execute(['idPension' => $idPension]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    public function buscarPorPersonaYMes(int $idPersona, DateTimeImmutable $mes): ?Pension
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM pension WHERE idPersona = :idPersona AND mes = :mes'
        );
        $consulta->execute(['idPersona' => $idPersona, 'mes' => $mes->format('Y-m-01')]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    public function listarPorTitular(int $idUsuario): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM pension WHERE idUsuario = :idUsuario ORDER BY mes DESC, idPension'
        );
        $consulta->execute(['idUsuario' => $idUsuario]);
        return array_map(fn (array $fila) => $this->aEntidad($fila), $consulta->fetchAll());
    }

    public function listarDesde(DateTimeImmutable $mes): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM pension WHERE mes >= :mes ORDER BY mes, idPension'
        );
        $consulta->execute(['mes' => $mes->format('Y-m-01')]);
        return array_map(fn (array $fila) => $this->aEntidad($fila), $consulta->fetchAll());
    }

    /** Guarda una pensión nueva (id 0) o actualiza una existente. Devuelve su id. */
    public function guardar(Pension $pension): int
    {
        $datos = [
            'idPersona' => $pension->getIdPersona(),
            'idUsuario' => $pension->getIdUsuario(),
            'mes' => $pension->getMes()->format('Y-m-01'),
            'tarifa' => $pension->getTarifa(),
            'estado' => $pension->getEstado()->value,
        ];

        try {
            if ($pension->getIdPension() === 0) {
                $this->pdo->prepare(
                    'INSERT INTO pension (idPersona, idUsuario, mes, tarifa, estado)
                     VALUES (:idPersona, :idUsuario, :mes, :tarifa, :estado)'
                )->execute($datos);
                return (int) $this->pdo->lastInsertId();
            }

            $datos['idPension'] = $pension->getIdPension();
            $this->pdo->prepare(
                'UPDATE pension SET idPersona = :idPersona, idUsuario = :idUsuario, mes = :mes,
                        tarifa = :tarifa, estado = :estado
                 WHERE idPension = :idPension'
            )->execute($datos);
            return $pension->getIdPension();
        } catch (PDOException $error) {
            // Segunda barrera contra duplicados: la restricción UNIQUE (persona, mes) de la base de datos.
            if ($error->getCode() === '23000') {
                throw new ReglaDeNegocioException('Esa persona ya tiene una pensión o una solicitud para ese mes.');
            }
            throw $error;
        }
    }

    public function eliminar(int $idPension): void
    {
        $this->pdo->prepare('DELETE FROM pension WHERE idPension = :idPension')->execute(['idPension' => $idPension]);
    }

    /** @param array<string, mixed> $fila */
    private function aEntidad(array $fila): Pension
    {
        return new Pension(
            (int) $fila['idPension'],
            (int) $fila['idPersona'],
            (int) $fila['idUsuario'],
            new DateTimeImmutable($fila['mes']),
            (float) $fila['tarifa'],
            EstadoPension::from($fila['estado']),
        );
    }
}