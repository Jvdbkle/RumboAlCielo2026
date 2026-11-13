<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Dominio\Entidades\Feriado;

/** Repositorio falso para las pruebas: guarda los feriados en memoria, sin base de datos. */
final class FeriadoRepositorioEnMemoria implements FeriadoRepositorio
{
    /** @var array<int, Feriado> */
    private array $feriados = [];

    public function buscarPorFecha(DateTimeImmutable $fecha): ?Feriado
    {
        foreach ($this->feriados as $feriado) {
            if ($feriado->esFeriado($fecha)) {
                return $feriado;
            }
        }
        return null;
    }

    public function listarTodos(): array
    {
        $lista = array_values($this->feriados);
        usort($lista, fn (Feriado $a, Feriado $b) => $a->getFecha() <=> $b->getFecha());
        return $lista;
    }

    public function guardar(Feriado $feriado): int
    {
        // Igual que la base de datos: un feriado nuevo (id 0) recibe el siguiente número.
        $id = $this->feriados === [] ? 1 : max(array_keys($this->feriados)) + 1;
        $this->feriados[$id] = new Feriado($id, $feriado->getFecha(), $feriado->getMotivo());
        return $id;
    }
}