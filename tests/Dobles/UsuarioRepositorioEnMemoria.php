<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Usuario;

/** Repositorio falso para las pruebas: guarda los usuarios en memoria, sin base de datos. */
final class UsuarioRepositorioEnMemoria implements UsuarioRepositorio
{
    /** @var array<int, Usuario> */
    private array $usuarios = [];

    public function buscarPorCorreo(string $correo): ?Usuario
    {
        foreach ($this->usuarios as $usuario) {
            if (strcasecmp($usuario->getCorreo(), $correo) === 0) {
                return $usuario;
            }
        }
        return null;
    }

    public function buscarPorId(int $idUsuario): ?Usuario
    {
        return $this->usuarios[$idUsuario] ?? null;
    }

    public function listarTodos(): array
    {
        return array_values($this->usuarios);
    }

    public function guardar(Usuario $usuario): int
    {
        if ($usuario->getIdUsuario() !== 0) {
            $this->usuarios[$usuario->getIdUsuario()] = $usuario;
            return $usuario->getIdUsuario();
        }

        // Igual que la base de datos: un usuario nuevo (id 0) recibe el siguiente número
        // y, al leerlo después, ya viene con ese número.
        $id = $this->usuarios === [] ? 1 : max(array_keys($this->usuarios)) + 1;
        $this->usuarios[$id] = new Usuario(
            $id,
            $usuario->getIdPersona(),
            $usuario->getCorreo(),
            $usuario->getContrasenia(),
            $usuario->getTelefono(),
            $usuario->getIdRol(),
            $usuario->getEstado(),
            $usuario->esPrimerIngreso(),
            $usuario->getIntentosFallidos(),
            $usuario->getBloqueadoHasta(),
        );
        return $id;
    }
}