<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Dominio\Entidades\Persona;

/** Repositorio falso para las pruebas: guarda las personas en memoria, sin base de datos. */
final class PersonaRepositorioEnMemoria implements PersonaRepositorio
{
    /** @var array<int, Persona> */
    private array $personas = [];
    private int $siguienteId = 1;

    public function buscarPorId(int $idPersona): ?Persona
    {
        return $this->personas[$idPersona] ?? null;
    }

    public function guardar(Persona $persona): int
    {
        if ($persona->getIdPersona() !== 0) {
            $this->personas[$persona->getIdPersona()] = $persona;
            return $persona->getIdPersona();
        }

        // Igual que la base de datos: una persona nueva (id 0) recibe el siguiente número.
        $id = $this->siguienteId++;
        $this->personas[$id] = new Persona($id, $persona->getNombres(), $persona->getApellidos());
        return $id;
    }
}