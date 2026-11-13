<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Dominio\Entidades\Familiar;

/** Repositorio falso para las pruebas: guarda los familiares en memoria, sin base de datos. */
final class FamiliarRepositorioEnMemoria implements FamiliarRepositorio
{
    /** @var array<int, Familiar> */
    private array $familiares = [];

    public function buscarPorId(int $idFamiliar): ?Familiar
    {
        return $this->familiares[$idFamiliar] ?? null;
    }

    public function listarActivosPorUsuario(int $idUsuario): array
    {
        return array_values(array_filter(
            $this->familiares,
            fn (Familiar $f) => $f->getIdUsuario() === $idUsuario && $f->estaActivo()
        ));
    }

    public function guardar(Familiar $familiar): int
    {
        if ($familiar->getIdFamiliar() !== 0) {
            $this->familiares[$familiar->getIdFamiliar()] = $familiar;
            return $familiar->getIdFamiliar();
        }

        // Igual que la base de datos: un familiar nuevo (id 0) recibe el siguiente número.
        $id = $this->familiares === [] ? 1 : max(array_keys($this->familiares)) + 1;
        $this->familiares[$id] = new Familiar(
            $id,
            $familiar->getIdPersona(),
            $familiar->getIdUsuario(),
            $familiar->getParentesco(),
            $familiar->getEstado(),
        );
        return $id;
    }
}