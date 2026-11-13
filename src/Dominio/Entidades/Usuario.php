<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoUsuario;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Dominio\Validacion\Validador;
final class Usuario
{
    /** HU-01 criterio 4: 3 fallos seguidos bloquean la cuenta 5 minutos. */
    public const MAX_INTENTOS = 3;
    public const MINUTOS_BLOQUEO = 5;
    public const LARGO_MINIMO = 8;
    /** La contraseña se guarda ya cifrada (hash), nunca en texto plano. */
    public function __construct(
        private int $idUsuario,
        private int $idPersona,
        private string $correo,
        private string $contrasenia,
        private string $telefono,
        private int $idRol,
        private EstadoUsuario $estado = EstadoUsuario::Habilitado,
        private bool $primerIngreso = true,
        private int $intentosFallidos = 0,
        private ?DateTimeImmutable $bloqueadoHasta = null,
    ) {
    }

       /**
     * HU-03 criterios 1, 6, 8 y 9: el correo es el login y el teléfono es la primera
     * contraseña. Si falta un dato o tiene formato inválido no se crea el usuario.
     */
    public static function registrarNuevo(
        int $idUsuario,
        int $idPersona,
        string $correo,
        string $telefono,
        int $idRol,
    ): self {
        $correo = Validador::correo($correo);
        $telefono = Validador::telefono($telefono);
        return new self(
            $idUsuario,
            $idPersona,
            $correo,
            password_hash($telefono, PASSWORD_DEFAULT),
            $telefono,
            $idRol,
        );
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getIdPersona(): int
    {
        return $this->idPersona;
    }

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function getContrasenia(): string
    {
        return $this->contrasenia;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function getIdRol(): int
    {
        return $this->idRol;
    }

    public function getEstado(): EstadoUsuario
    {
        return $this->estado;
    }

    public function esPrimerIngreso(): bool
    {
        return $this->primerIngreso;
    }

    public function getIntentosFallidos(): int
    {
        return $this->intentosFallidos;
    }

    public function getBloqueadoHasta(): ?DateTimeImmutable
    {
        return $this->bloqueadoHasta;
    }

    public function estaHabilitado(): bool
    {
        return $this->estado === EstadoUsuario::Habilitado;
    }

     public function setCorreo(string $correo): void
    {
        $this->correo = Validador::correo($correo);
    }

    public function setTelefono(string $telefono): void
    {
        $this->telefono = Validador::telefono($telefono);
    }

    /**
     * HU-01 criterios 1, 2 y 4. Devuelve true solo si las credenciales son correctas
     * y la cuenta está habilitada y no bloqueada. Los fallos se cuentan aquí.
     */
    public function iniciarSesion(string $correo, string $contrasenia): bool
    {
        if (!$this->estaHabilitado() || $this->estaBloqueado()) {
            return false;
        }

        $correcto = strcasecmp($this->correo, $correo) === 0
            && password_verify($contrasenia, $this->contrasenia);

        if ($correcto) {
            $this->intentosFallidos = 0;
            $this->bloqueadoHasta = null;
            return true;
        }

        $this->intentosFallidos++;
        if ($this->intentosFallidos >= self::MAX_INTENTOS) {
            $this->bloqueadoHasta = Reloj::ahora()->modify('+' . self::MINUTOS_BLOQUEO . ' minutes');
            $this->intentosFallidos = 0;
        }
        return false;
    }

    /**
     * HU-05. La sesión web la destruye la infraestructura (el dominio no depende de ella);
     * aquí no queda estado de sesión que limpiar.
     */
    public function cerrarSesion(): void
    {
    }

      /**
     * HU-01 criterio 3: tras cambiar la contraseña deja de ser primer ingreso.
     * HU-01 criterio 5: debe cumplir la política de seguridad.
     */
    public function cambiarContrasenia(string $nueva): void
    {
        if (trim($nueva) === '') {
            throw new ReglaDeNegocioException('La nueva contraseña no puede estar vacía.');
        }
        if (!$this->cumpleRequisitos($nueva)) {
            throw new ReglaDeNegocioException(
                'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, '
                . 'un número y un símbolo especial, y ser distinta de tu teléfono.'
            );
        }
        $this->contrasenia = password_hash($nueva, PASSWORD_DEFAULT);
        $this->primerIngreso = false;
    }

    /** HU-01 criterio 5. La contraseña inicial (el teléfono) no pasa por aquí. */
    private function cumpleRequisitos(string $contrasenia): bool
    {
        return mb_strlen($contrasenia) >= self::LARGO_MINIMO
            && preg_match('/\p{Lu}/u', $contrasenia) === 1
            && preg_match('/\p{Ll}/u', $contrasenia) === 1
            && preg_match('/\d/', $contrasenia) === 1
            && preg_match('/[^\p{L}\d\s]/u', $contrasenia) === 1
            && $contrasenia !== $this->telefono;
    }

       /**
     * HU-02 criterio 1: el teléfono vuelve a ser la contraseña.
     * Como es una clave conocida, se vuelve a exigir el cambio al ingresar (HU-01 criterio 3).
     */
    public function reestablecerContrasenia(): void
    {
        $this->contrasenia = password_hash($this->telefono, PASSWORD_DEFAULT);
        $this->primerIngreso = true;
    }

    /** HU-03 criterio 2. */
    public function deshabilitar(): void
    {
        $this->estado = EstadoUsuario::Deshabilitado;
    }

    /** HU-06 criterios 1 y 3: no se puede reincorporar a quien ya está habilitado. */
    public function habilitar(): void
    {
        if ($this->estaHabilitado()) {
            throw new ReglaDeNegocioException('El usuario ya está habilitado.');
        }
        $this->estado = EstadoUsuario::Habilitado;
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueadoHasta !== null && Reloj::ahora() < $this->bloqueadoHasta;
    }
}