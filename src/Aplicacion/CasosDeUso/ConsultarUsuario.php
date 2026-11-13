<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-03 criterio 4: el administrador abre los datos de un usuario para modificarlos. */
final class ConsultarUsuario
{
    public function __construct(
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
    ) {
    }

    public function ejecutar(int $idUsuario): DatosUsuario
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        $persona = $usuario === null ? null : $this->personas->buscarPorId($usuario->getIdPersona());
        if ($usuario === null || $persona === null) {
            throw new ReglaDeNegocioException('No se encontró al usuario.');
        }

        return new DatosUsuario(
            $usuario->getIdUsuario(),
            $persona->getNombres(),
            $persona->getApellidos(),
            $usuario->getCorreo(),
            $usuario->getTelefono(),
            $usuario->getIdRol(),
        );
    }
}