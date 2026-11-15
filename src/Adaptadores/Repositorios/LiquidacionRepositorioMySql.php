<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use RumboAlCielo\Aplicacion\Repositorios\LiquidacionRepositorio;
use RumboAlCielo\Dominio\Entidades\DetalleLiquidacion;
use RumboAlCielo\Dominio\Entidades\Liquidacion;
use RumboAlCielo\Dominio\Enumeraciones\EstadoLiquidacion;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use Throwable;

final class LiquidacionRepositorioMySql implements LiquidacionRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorMes(DateTimeImmutable $mes): ?Liquidacion
    {
        $consulta = $this->pdo->prepare('SELECT idLiquidacion, mes, estado FROM liquidacion WHERE mes = :mes');
        $consulta->execute(['mes' => $mes->modify('first day of this month')->format('Y-m-d')]);
        $fila = $consulta->fetch();
        if ($fila === false) {
            return null;
        }

        $liquidacion = new Liquidacion((int) $fila['idLiquidacion'], new DateTimeImmutable($fila['mes']), EstadoLiquidacion::from($fila['estado']));
        $detalles = $this->pdo->prepare(
            'SELECT idDetalle, idLiquidacion, idPersona, idUsuario, modalidad, cantidad, penalidad, total
             FROM detalle_liquidacion WHERE idLiquidacion = :id ORDER BY idUsuario, idPersona'
        );
        $detalles->execute(['id' => $liquidacion->getIdLiquidacion()]);
        foreach ($detalles->fetchAll() as $d) {
            $liquidacion->agregarDetalle(new DetalleLiquidacion(
                (int) $d['idDetalle'],
                (int) $d['idLiquidacion'],
                (int) $d['idPersona'],
                (int) $d['idUsuario'],
                Modalidad::from($d['modalidad']),
                (int) $d['cantidad'],
                (float) $d['penalidad'],
                (float) $d['total'],
            ));
        }
        return $liquidacion;
    }

    public function guardar(Liquidacion $liquidacion): int
    {
        $this->pdo->beginTransaction();
        try {
            $id = $liquidacion->getIdLiquidacion();
            if ($id === 0) {
                $this->pdo->prepare('INSERT INTO liquidacion (mes, estado) VALUES (:mes, :estado)')->execute([
                    'mes' => $liquidacion->getMes()->format('Y-m-d'),
                    'estado' => $liquidacion->getEstado()->value,
                ]);
                $id = (int) $this->pdo->lastInsertId();
            } else {
                $this->pdo->prepare('UPDATE liquidacion SET estado = :estado WHERE idLiquidacion = :id')->execute([
                    'estado' => $liquidacion->getEstado()->value,
                    'id' => $id,
                ]);
            }

            // Los detalles se reemplazan completos: la liquidación se vuelve a calcular entera.
            $this->pdo->prepare('DELETE FROM detalle_liquidacion WHERE idLiquidacion = :id')->execute(['id' => $id]);
            $insertar = $this->pdo->prepare(
                'INSERT INTO detalle_liquidacion (idLiquidacion, idPersona, idUsuario, modalidad, cantidad, penalidad, total)
                 VALUES (:idLiquidacion, :idPersona, :idUsuario, :modalidad, :cantidad, :penalidad, :total)'
            );
            foreach ($liquidacion->getDetalles() as $d) {
                $insertar->execute([
                    'idLiquidacion' => $id,
                    'idPersona' => $d->getIdPersona(),
                    'idUsuario' => $d->getIdUsuario(),
                    'modalidad' => $d->getModalidad()->value,
                    'cantidad' => $d->getCantidad(),
                    'penalidad' => $d->getPenalidad(),
                    'total' => $d->getTotal(),
                ]);
            }
            $this->pdo->commit();
            return $id;
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }
}