<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Entidades\Usuario;
use RumboAlCielo\Dominio\Enumeraciones\EstadoUsuario;

final class UsuarioRepositorioMySql implements UsuarioRepositorio
{
    private const COLUMNAS = 'idUsuario, idPersona, correo, contrasenia, telefono, estado, idRol, primerIngreso, intentosFallidos, bloqueadoHasta';

    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorCorreo(string $correo): ?Usuario
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM usuario WHERE correo = :correo');
        $consulta->execute(['correo' => $correo]);
        return $this->aEntidad($consulta->fetch());
    }

    public function buscarPorId(int $idUsuario): ?Usuario
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM usuario WHERE idUsuario = :idUsuario');
        $consulta->execute(['idUsuario' => $idUsuario]);
        return $this->aEntidad($consulta->fetch());
    }

    /** Guarda un usuario nuevo (id 0) o actualiza uno existente. Devuelve su id. */
    public function guardar(Usuario $usuario): int
    {
        $datos = [
            'idPersona' => $usuario->getIdPersona(),
            'correo' => $usuario->getCorreo(),
            'contrasenia' => $usuario->getContrasenia(),
            'telefono' => $usuario->getTelefono(),
            'estado' => $usuario->getEstado()->value,
            'idRol' => $usuario->getIdRol(),
            'primerIngreso' => $usuario->esPrimerIngreso() ? 1 : 0,
            'intentosFallidos' => $usuario->getIntentosFallidos(),
            'bloqueadoHasta' => $usuario->getBloqueadoHasta()?->format('Y-m-d H:i:s'),
        ];

        if ($usuario->getIdUsuario() === 0) {
            $this->pdo->prepare(
                'INSERT INTO usuario (idPersona, correo, contrasenia, telefono, estado, idRol, primerIngreso, intentosFallidos, bloqueadoHasta)
                 VALUES (:idPersona, :correo, :contrasenia, :telefono, :estado, :idRol, :primerIngreso, :intentosFallidos, :bloqueadoHasta)'
            )->execute($datos);
            return (int) $this->pdo->lastInsertId();
        }

        $datos['idUsuario'] = $usuario->getIdUsuario();
        $this->pdo->prepare(
            'UPDATE usuario SET idPersona = :idPersona, correo = :correo, contrasenia = :contrasenia, telefono = :telefono,
                estado = :estado, idRol = :idRol, primerIngreso = :primerIngreso, intentosFallidos = :intentosFallidos,
                bloqueadoHasta = :bloqueadoHasta WHERE idUsuario = :idUsuario'
        )->execute($datos);
        return $usuario->getIdUsuario();
    }

    /** @param array<string, mixed>|false $fila */
    private function aEntidad(array|false $fila): ?Usuario
    {
        if ($fila === false) {
            return null;
        }
        return new Usuario(
            (int) $fila['idUsuario'],
            (int) $fila['idPersona'],
            $fila['correo'],
            $fila['contrasenia'],
            $fila['telefono'],
            (int) $fila['idRol'],
            EstadoUsuario::from($fila['estado']),
            (bool) $fila['primerIngreso'],
            (int) $fila['intentosFallidos'],
            $fila['bloqueadoHasta'] !== null ? new DateTimeImmutable($fila['bloqueadoHasta']) : null,
        );
    }
}