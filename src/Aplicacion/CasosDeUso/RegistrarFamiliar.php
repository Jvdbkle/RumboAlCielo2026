<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FamiliarRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Familiar;
use RumboAlCielo\Dominio\Entidades\Persona;
use RumboAlCielo\Dominio\Enumeraciones\Parentesco;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-04 criterios 1, 4 y 5: el administrador registra a un familiar y lo vincula a un
 * empleado. El parentesco solo puede ser cónyuge, hijo o hija.
 */
final class RegistrarFamiliar
{
    public function __construct(
        private FamiliarRepositorio $familiares,
        private PersonaRepositorio $personas,
        private UsuarioRepositorio $usuarios,
        private RolRepositorio $roles,
    ) {
    }

    /** Devuelve el id del familiar creado. */
    public function ejecutar(int $idEmpleado, string $nombres, string $apellidos, string $parentesco): int
    {
        // Primero se valida todo: si algo falla, no se guarda nada.
        $persona = Persona::registrar(0, $nombres, $apellidos);

        $parentesco = Parentesco::tryFrom($parentesco);
        if ($parentesco === null) {
            throw new ReglaDeNegocioException('El parentesco solo puede ser cónyuge, hijo o hija.');
        }

        $empleado = $this->usuarios->buscarPorId($idEmpleado);
        $rol = $empleado === null ? null : $this->roles->buscarPorId($empleado->getIdRol());
        if ($rol === null || $rol->esAdministrador()) {
            throw new ReglaDeNegocioException('El familiar debe vincularse a un empleado registrado.');
        }

        $idPersona = $this->personas->guardar($persona);
        return $this->familiares->guardar(new Familiar(0, $idPersona, $idEmpleado, $parentesco));
    }
}