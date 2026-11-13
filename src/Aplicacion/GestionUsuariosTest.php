<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\DeshabilitarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarUsuarios;
use RumboAlCielo\Aplicacion\CasosDeUso\ReestablecerContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\ReincorporarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-02 Recuperar contraseña, HU-03 Gestión de Usuarios (criterios 2, 3 y 4) y HU-06 Reincorporar. */
final class GestionUsuariosTest extends TestCase
{
    private const ADMIN_ID = 1;
    private const JUAN_ID = 2;

    private UsuarioRepositorioEnMemoria $usuarios;
    private PersonaRepositorioEnMemoria $personas;
    private RolRepositorioEnMemoria $roles;
    private IniciarSesion $iniciarSesion;

    protected function setUp(): void
    {
        $this->roles = new RolRepositorioEnMemoria();
        $this->roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $this->roles->agregar(new Rol(2, Rol::EMPLEADO));

        $this->usuarios = new UsuarioRepositorioEnMemoria();
        $this->personas = new PersonaRepositorioEnMemoria();

        $registrar = new RegistrarUsuario($this->usuarios, $this->personas, $this->roles);
        $registrar->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);   // administradora (id 1)
        $registrar->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2); // empleado (id 2)

        $this->iniciarSesion = new IniciarSesion($this->usuarios, $this->roles);
    }

    /** HU-03 criterio 2: un usuario deshabilitado no puede ingresar y ve "Usuario deshabilitado". */
    public function test_un_usuario_deshabilitado_no_puede_ingresar(): void
    {
        (new DeshabilitarUsuario($this->usuarios))->ejecutar(self::JUAN_ID);

        $mensaje = $this->mensajeDeError(fn () => $this->iniciarSesion->ejecutar('juan@correo.com', '70000000'));

        $this->assertSame('Usuario deshabilitado', $mensaje);
    }

    /** HU-03 criterio 3: la lista muestra los usuarios con su estado. */
    public function test_la_lista_muestra_los_usuarios_con_su_estado(): void
    {
        (new DeshabilitarUsuario($this->usuarios))->ejecutar(self::JUAN_ID);

        $filas = (new ListarUsuarios($this->usuarios, $this->personas, $this->roles))->ejecutar();

        $this->assertSame(2, count($filas));
        $this->assertSame('Ana Rojas', $filas[0]->nombreCompleto);
        $this->assertSame('Habilitado', $filas[0]->estado);
        $this->assertSame('Juan Pérez', $filas[1]->nombreCompleto);
        $this->assertSame('Deshabilitado', $filas[1]->estado);
    }

    /** HU-03 criterio 4: el administrador modifica los datos y el sistema los actualiza. */
    public function test_edita_los_datos_de_un_usuario(): void
    {
        (new EditarUsuario($this->usuarios, $this->personas))
            ->ejecutar(self::JUAN_ID, 'Juan Carlos', 'Pérez', 'jcperez@correo.com', '72222222');

        $filas = (new ListarUsuarios($this->usuarios, $this->personas, $this->roles))->ejecutar();
        $this->assertSame('Juan Carlos Pérez', $filas[1]->nombreCompleto);
        $this->assertSame('jcperez@correo.com', $filas[1]->correo);
        $this->assertSame('72222222', $this->usuarios->buscarPorId(self::JUAN_ID)->getTelefono());
    }

    /** HU-03 criterio 5 (al editar): no se puede usar el correo de otro usuario, pero sí conservar el propio. */
    public function test_al_editar_no_se_puede_usar_el_correo_de_otro_usuario(): void
    {
        $editar = new EditarUsuario($this->usuarios, $this->personas);

        $mensaje = $this->mensajeDeError(
            fn () => $editar->ejecutar(self::JUAN_ID, 'Juan', 'Pérez', 'ana@correo.com', '70000000')
        );
        $this->assertSame('El correo ya está registrado.', $mensaje);

        // Conservar su propio correo no es un duplicado.
        $editar->ejecutar(self::JUAN_ID, 'Juan', 'Pérez', 'juan@correo.com', '70000000');
        $this->assertSame('juan@correo.com', $this->usuarios->buscarPorId(self::JUAN_ID)->getCorreo());
    }

    /** HU-03 criterios 7 a 9 (al editar): un dato inválido no modifica ninguno de los datos. */
    public function test_al_editar_un_dato_invalido_no_cambia_nada(): void
    {
        $mensaje = $this->mensajeDeError(
            fn () => (new EditarUsuario($this->usuarios, $this->personas))
                ->ejecutar(self::JUAN_ID, 'Juan', 'Pérez', 'nuevo@correo.com', '7000')
        );

        $this->assertSame('El teléfono debe tener exactamente 8 dígitos y solo números.', $mensaje);
        $this->assertSame('juan@correo.com', $this->usuarios->buscarPorId(self::JUAN_ID)->getCorreo());
    }

    /** HU-06 criterio 1: al reincorporar, el usuario vuelve a ingresar al sistema. */
    public function test_un_usuario_reincorporado_puede_ingresar_de_nuevo(): void
    {
        (new DeshabilitarUsuario($this->usuarios))->ejecutar(self::JUAN_ID);
        (new ReincorporarUsuario($this->usuarios))->ejecutar(self::JUAN_ID);

        $resultado = $this->iniciarSesion->ejecutar('juan@correo.com', '70000000');

        $this->assertSame('juan@correo.com', $resultado->usuario->getCorreo());
    }

    /** HU-06 criterio 2: la baja no borra al usuario, por eso conserva su registro anterior. */
    public function test_la_baja_no_borra_al_usuario_ni_sus_datos(): void
    {
        (new DeshabilitarUsuario($this->usuarios))->ejecutar(self::JUAN_ID);

        $filas = (new ListarUsuarios($this->usuarios, $this->personas, $this->roles))->ejecutar();

        $this->assertSame(2, count($filas));
        $this->assertSame(self::JUAN_ID, $filas[1]->idUsuario);
        $this->assertSame('Juan Pérez', $filas[1]->nombreCompleto);
    }

    /** HU-06 criterio 3: no se puede reincorporar a quien ya está habilitado. */
    public function test_no_se_reincorpora_a_un_usuario_ya_habilitado(): void
    {
        $mensaje = $this->mensajeDeError(fn () => (new ReincorporarUsuario($this->usuarios))->ejecutar(self::JUAN_ID));

        $this->assertSame('El usuario ya está habilitado.', $mensaje);
    }

    /** HU-02 criterio 1: el administrador reestablece y el teléfono vuelve a ser la contraseña. */
    public function test_el_administrador_reestablece_la_contrasenia_al_telefono(): void
    {
        $this->usuarios->buscarPorId(self::JUAN_ID)->cambiarContrasenia('Clave#2026x');

        (new ReestablecerContrasenia($this->usuarios, $this->roles))->ejecutar(self::ADMIN_ID, self::JUAN_ID);

        $resultado = $this->iniciarSesion->ejecutar('juan@correo.com', '70000000');
        $this->assertTrue($resultado->requiereCambioContrasenia);
    }

    /** HU-02 criterio 2: un empleado no puede reestablecer la contraseña de otro usuario. */
    public function test_un_empleado_no_puede_reestablecer_contrasenias(): void
    {
        $mensaje = $this->mensajeDeError(
            fn () => (new ReestablecerContrasenia($this->usuarios, $this->roles))->ejecutar(self::JUAN_ID, self::ADMIN_ID)
        );

        $this->assertSame('Solo el administrador puede reestablecer contraseñas.', $mensaje);
    }

    private function mensajeDeError(callable $accion): string
    {
        try {
            $accion();
        } catch (ReglaDeNegocioException $error) {
            return $error->getMessage();
        }
        $this->fail('Se esperaba que el sistema rechazara la operación.');
    }
}