<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\PensionRepositorio;
use RumboAlCielo\Dominio\Entidades\Pension;

/** Repositorio falso para las pruebas: guarda las pensiones en memoria, sin base de datos. */
final class PensionRepositorioEnMemoria implements PensionRepositorio
{
    /** @var array<int, Pension> */
    private array $pensiones = [];

    public function buscarPorId(int $idPension): ?Pension
    {
        return $this->pensiones[$idPension] ?? null;
    }

    public function buscarPorPersonaYMes(int $idPersona, DateTimeImmutable $mes): ?Pension
    {
        foreach ($this->pensiones as $pension) {
            if ($pension->getIdPersona() === $idPersona && $pension->getMes() == $mes) {
                return $pension;
            }
        }
        return null;
    }

    public function listarPorTitular(int $idUsuario): array
    {
        $lista = array_values(array_filter($this->pensiones, fn (Pension $p) => $p->getIdUsuario() === $idUsuario));
        usort($lista, fn (Pension $a, Pension $b) => $b->getMes() <=> $a->getMes());
        return $lista;
    }

    public function listarDesde(DateTimeImmutable $mes): array
    {
        $lista = array_values(array_filter($this->pensiones, fn (Pension $p) => $p->getMes() >= $mes));
        usort($lista, fn (Pension $a, Pension $b) => $a->getMes() <=> $b->getMes());
        return $lista;
    }

    public function guardar(Pension $pension): int
    {
        if ($pension->getIdPension() !== 0) {
            $this->pensiones[$pension->getIdPension()] = $pension;
            return $pension->getIdPension();
        }

        // Igual que la base de datos: una pensión nueva (id 0) recibe el siguiente número.
        $id = $this->pensiones === [] ? 1 : max(array_keys($this->pensiones)) + 1;
        $this->pensiones[$id] = new Pension(
            $id,
            $pension->getIdPersona(),
            $pension->getIdUsuario(),
            $pension->getMes(),
            $pension->getTarifa(),
            $pension->getEstado(),
        );
        return $id;
    }

    public function eliminar(int $idPension): void
    {
        unset($this->pensiones[$idPension]);
    }
}