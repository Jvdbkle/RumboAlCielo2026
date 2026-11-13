<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Entidades\Usuario;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-01 Inicio de sesión (y HU-03 criterio 2 para el usuario deshabilitado). */
final class IniciarSesionTest extends TestCase
{
    private const CORREO = 'juan@correo.com';
    private const TELEFONO = '70000000';

    private UsuarioRepositorioEnMemoria $usuarios;
    private IniciarSesion $iniciarSesion;
    private Usuario $juan;

    protected function setUp(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:00'));

        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        // El correo es el login y el teléfono es la primera contraseña (HU-03 criterio 1).
        $this->juan = Usuario::registrarNuevo(1, 1, self::CORREO, self::TELEFONO, 2);
        $this->usuarios = new UsuarioRepositorioEnMemoria();
        $this->usuarios->guardar($this->juan);

        $this->iniciarSesion = new IniciarSesion($this->usuarios, $roles);
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
    }

    /** HU-01 criterio 1: usuario registrado con credenciales correctas entra con su rol. */
    public function test_credenciales_correctas_devuelven_el_usuario_con_su_rol(): void
    {
        $resultado = $this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO);

        $this->assertSame(self::CORREO, $resultado->usuario->getCorreo());
        $this->assertSame(Rol::EMPLEADO, $resultado->rol->getNombreRol());
    }

    /** HU-01 criterio 2: el mensaje no dice si falló el correo o la contraseña. */
    public function test_correo_o_contrasenia_incorrecta_muestran_el_mismo_mensaje(): void
    {
        $conCorreoMalo = $this->mensajeDeError(fn () => $this->iniciarSesion->ejecutar('otro@correo.com', self::TELEFONO));
        $conClaveMala = $this->mensajeDeError(fn () => $this->iniciarSesion->ejecutar(self::CORREO, 'clave-mala'));

        $this->assertSame('Usuario o Contraseña incorrecta', $conCorreoMalo);
        $this->assertSame($conCorreoMalo, $conClaveMala);
    }

    /** HU-01 criterio 3: en el primer ingreso (correo y teléfono) se pide cambiar la contraseña. */
    public function test_primer_ingreso_pide_el_cambio_de_contrasenia(): void
    {
        $resultado = $this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO);

        $this->assertTrue($resultado->requiereCambioContrasenia);
    }

    /** HU-01 criterio 3: después de cambiar la contraseña ya no se vuelve a pedir. */
    public function test_despues_de_cambiar_la_contrasenia_ya_no_se_pide_el_cambio(): void
    {
        $this->juan->cambiarContrasenia('nueva-clave');

        $resultado = $this->iniciarSesion->ejecutar(self::CORREO, 'nueva-clave');

        $this->assertFalse($resultado->requiereCambioContrasenia);
    }

    /** HU-01 criterio 4: tras 3 fallos seguidos la cuenta queda bloqueada 5 minutos. */
    public function test_tres_fallos_bloquean_la_cuenta_por_cinco_minutos(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->mensajeDeError(fn () => $this->iniciarSesion->ejecutar(self::CORREO, 'clave-mala'));
        }

        // Ni la contraseña correcta entra mientras dura el bloqueo.
        $mensaje = $this->mensajeDeError(fn () => $this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO));
        $this->assertSame('La cuenta está bloqueada por 5 minutos', $mensaje);

        // Pasados los 5 minutos puede ingresar de nuevo.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:06'));
        $resultado = $this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO);
        $this->assertSame(self::CORREO, $resultado->usuario->getCorreo());
    }

    /** HU-03 criterio 2: un usuario deshabilitado no puede ingresar. */
    public function test_usuario_deshabilitado_no_puede_ingresar(): void
    {
        $this->juan->deshabilitar();

        $mensaje = $this->mensajeDeError(fn () => $this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO));

        $this->assertSame('Usuario deshabilitado', $mensaje);
    }

    private function mensajeDeError(callable $accion): string
    {
        try {
            $accion();
        } catch (ReglaDeNegocioException $e) {
            return $e->getMessage();
        }
        $this->fail('Se esperaba que el sistema rechazara la operación.');
    }
}