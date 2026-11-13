<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Persona;
use RumboAlCielo\Dominio\Entidades\Usuario;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Validacion\Validador;

/**
 * HU-03 criterios 1, 5, 6, 7, 8 y 9: el administrador registra un usuario nuevo.
 * El correo es el login y el teléfono es la primera contraseña.
 */
final class RegistrarUsuario
{
    public function __construct(
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
        private RolRepositorio $roles,
    ) {
    }

    /** Devuelve el id del usuario creado. */
    public function ejecutar(string $nombres, string $apellidos, string $correo, string $telefono, int $idRol): int
    {
        // Primero se valida todo: si algo falla, no se guarda nada (criterios 6 a 9).
        $persona = Persona::registrar(0, $nombres, $apellidos);
        $correo = Validador::correo($correo);
        Validador::telefono($telefono);

        if ($this->roles->buscarPorId($idRol) === null) {
            throw new ReglaDeNegocioException('El rol seleccionado no existe.');
        }
        // Criterio 5: el correo no puede repetirse.
        if ($this->usuarios->buscarPorCorreo($correo) !== null) {
            throw new ReglaDeNegocioException('El correo ya está registrado.');
        }

        $idPersona = $this->personas->guardar($persona);
        return $this->usuarios->guardar(Usuario::registrarNuevo(0, $idPersona, $correo, $telefono, $idRol));
    }
}