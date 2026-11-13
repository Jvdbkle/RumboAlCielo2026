<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use RumboAlCielo\Dominio\Entidades\Usuario;

interface UsuarioRepositorio
{
    public function buscarPorCorreo(string $correo): ?Usuario;

    public function buscarPorId(int $idUsuario): ?Usuario;

    /** Guarda un usuario nuevo o actualiza uno existente. Devuelve su id. */
    public function guardar(Usuario $usuario): int;
}