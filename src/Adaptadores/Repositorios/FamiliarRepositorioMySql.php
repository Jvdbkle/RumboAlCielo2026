<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use PDO;
use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Dominio\Entidades\Familiar;
use RumboAlCielo\Dominio\Enumeraciones\EstadoFamiliar;
use RumboAlCielo\Dominio\Enumeraciones\Parentesco;

final class FamiliarRepositorioMySql implements FamiliarRepositorio
{
    private const COLUMNAS = 'idFamiliar, idPersona, idUsuario, parentesco, estado';

    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $idFamiliar): ?Familiar
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM familiar WHERE idFamiliar = :idFamiliar');
        $consulta->execute(['idFamiliar' => $idFamiliar]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    public function listarActivosPorUsuario(int $idUsuario): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM familiar WHERE idUsuario = :idUsuario AND estado = :estado ORDER BY idFamiliar'
        );
        $consulta->execute(['idUsuario' => $idUsuario, 'estado' => EstadoFamiliar::Activo->value]);
        return array_map(fn (array $fila) => $this->aEntidad($fila), $consulta->fetchAll());
    }

    /** Guarda un familiar nuevo (id 0) o actualiza uno existente. Devuelve su id. */
    public function guardar(Familiar $familiar): int
    {
        $datos = [
            'idPersona' => $familiar->getIdPersona(),
            'idUsuario' => $familiar->getIdUsuario(),
            'parentesco' => $familiar->getParentesco()->value,
            'estado' => $familiar->getEstado()->value,
        ];

        if ($familiar->getIdFamiliar() === 0) {
            $this->pdo->prepare(
                'INSERT INTO familiar (idPersona, idUsuario, parentesco, estado) VALUES (:idPersona, :idUsuario, :parentesco, :estado)'
            )->execute($datos);
            return (int) $this->pdo->lastInsertId();
        }

        $datos['idFamiliar'] = $familiar->getIdFamiliar();
        $this->pdo->prepare(
            'UPDATE familiar SET idPersona = :idPersona, idUsuario = :idUsuario, parentesco = :parentesco, estado = :estado
             WHERE idFamiliar = :idFamiliar'
        )->execute($datos);
        return $familiar->getIdFamiliar();
    }

    /** @param array<string, mixed> $fila */
    private function aEntidad(array $fila): Familiar
    {
        return new Familiar(
            (int) $fila['idFamiliar'],
            (int) $fila['idPersona'],
            (int) $fila['idUsuario'],
            Parentesco::from($fila['parentesco']),
            EstadoFamiliar::from($fila['estado']),
        );
    }
}