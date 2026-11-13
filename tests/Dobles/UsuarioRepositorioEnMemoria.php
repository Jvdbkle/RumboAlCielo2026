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

    public function guardar(Usuario $usuario): int
    {
        $this->usuarios[$usuario->getIdUsuario()] = $usuario;
        return $usuario->getIdUsuario();
    }
}