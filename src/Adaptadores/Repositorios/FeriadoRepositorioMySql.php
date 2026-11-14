<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use PDOException;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Dominio\Entidades\Feriado;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class FeriadoRepositorioMySql implements FeriadoRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorFecha(DateTimeImmutable $fecha): ?Feriado
    {
        $consulta = $this->pdo->prepare('SELECT idFeriado, fecha, motivo FROM feriado WHERE fecha = :fecha');
        $consulta->execute(['fecha' => $fecha->format('Y-m-d')]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $this->aEntidad($fila);
    }

    /** @return Feriado[] */
    public function listarTodos(): array
    {
        $filas = $this->pdo->query('SELECT idFeriado, fecha, motivo FROM feriado ORDER BY fecha')->fetchAll();
        return array_map(fn (array $fila) => $this->aEntidad($fila), $filas);
    }

    /** Guarda un feriado nuevo (id 0). Devuelve su id. */
    public function guardar(Feriado $feriado): int
    {
        try {
            $this->pdo->prepare('INSERT INTO feriado (fecha, motivo) VALUES (:fecha, :motivo)')->execute([
                'fecha' => $feriado->getFecha()->format('Y-m-d'),
                'motivo' => $feriado->getMotivo(),
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $error) {
            // Segunda barrera contra fechas repetidas: la restricción UNIQUE de la base de datos.
            if ($error->getCode() === '23000') {
                throw new ReglaDeNegocioException('Esa fecha ya está registrada como día sin atención.');
            }
            throw $error;
        }
    }

    /** @param array<string, mixed> $fila */
    private function aEntidad(array $fila): Feriado
    {
        return new Feriado((int) $fila['idFeriado'], new DateTimeImmutable($fila['fecha']), $fila['motivo']);
    }
}