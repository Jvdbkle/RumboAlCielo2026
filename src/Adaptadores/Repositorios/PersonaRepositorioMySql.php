<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use PDO;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Dominio\Entidades\Persona;

final class PersonaRepositorioMySql implements PersonaRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $idPersona): ?Persona
    {
        $consulta = $this->pdo->prepare('SELECT idPersona, nombres, apellidos FROM persona WHERE idPersona = :idPersona');
        $consulta->execute(['idPersona' => $idPersona]);
        $fila = $consulta->fetch();
        if ($fila === false) {
            return null;
        }
        return new Persona((int) $fila['idPersona'], $fila['nombres'], $fila['apellidos']);
    }

    /** Guarda una persona nueva (id 0) o actualiza una existente. Devuelve su id. */
    public function guardar(Persona $persona): int
    {
        $datos = ['nombres' => $persona->getNombres(), 'apellidos' => $persona->getApellidos()];

        if ($persona->getIdPersona() === 0) {
            $this->pdo->prepare('INSERT INTO persona (nombres, apellidos) VALUES (:nombres, :apellidos)')->execute($datos);
            return (int) $this->pdo->lastInsertId();
        }

        $datos['idPersona'] = $persona->getIdPersona();
        $this->pdo->prepare('UPDATE persona SET nombres = :nombres, apellidos = :apellidos WHERE idPersona = :idPersona')
            ->execute($datos);
        return $persona->getIdPersona();
    }
}