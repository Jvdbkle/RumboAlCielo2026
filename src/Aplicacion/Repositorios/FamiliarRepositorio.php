<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use RumboAlCielo\Dominio\Entidades\Familiar;

interface FamiliarRepositorio
{
    public function buscarPorId(int $idFamiliar): ?Familiar;

    /** @return Familiar[] Los familiares activos (no eliminados) de un empleado. */
    public function listarActivosPorUsuario(int $idUsuario): array;

    /** Guarda un familiar nuevo (id 0) o actualiza uno existente. Devuelve su id. */
    public function guardar(Familiar $familiar): int;
}