<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use RumboAlCielo\Dominio\Entidades\Persona;

interface PersonaRepositorio
{
    public function buscarPorId(int $idPersona): ?Persona;

    /**
     * Guarda la persona y devuelve su id. Si el id es 0 se crea una nueva;
     * si ya tiene id se actualiza.
     */
    public function guardar(Persona $persona): int;
}