<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\EliminarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFamiliares;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Tests\Dobles\FamiliarRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-04 Gestión de Familiares y HU-07 Eliminar un familiar (criterios 1 y 3). */
final class GestionFamiliaresTest extends TestCase
{
    private const EMPLEADO_ID = 2;

    private FamiliarRepositorioEnMemoria $familiares;
    private PersonaRepositorioEnMemoria $personas;
    private RegistrarFamiliar $registrarFamiliar;
    private ListarFamiliares $listarFamiliares;

    protected function setUp(): void
    {
        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $usuarios = new UsuarioRepositorioEnMemoria();
        $this->personas = new PersonaRepositorioEnMemoria();
        $this->familiares = new FamiliarRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($usuarios, $this->personas, $roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);   // administradora (id 1)
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2); // empleado (id 2)

        $this->registrarFamiliar = new RegistrarFamiliar($this->familiares, $this->personas, $usuarios, $roles);
        $this->listarFamiliares = new ListarFamiliares($this->familiares, $this->personas);
    }

    /** HU-04 criterio 1: el administrador registra a un familiar vinculado a un empleado. */
    public function test_registra_un_familiar_vinculado_a_un_empleado(): void
    {
        $id = $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'María', 'López', 'conyuge');

        $familiar = $this->familiares->buscarPorId($id);
        $this->assertSame(self::EMPLEADO_ID, $familiar->getIdUsuario());
        $this->assertTrue($familiar->estaActivo());
    }

    /** HU-04 criterio 2: el empleado consulta sus familiares y ve los agregados. */
    public function test_el_empleado_ve_sus_familiares_agregados(): void
    {
        $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'María', 'López', 'conyuge');
        $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'Pedro', 'Pérez', 'hijo');

        $filas = $this->listarFamiliares->ejecutar(self::EMPLEADO_ID);

        $this->assertSame(2, count($filas));
        $this->assertSame('María López', $filas[0]->nombreCompleto);
        $this->assertSame('Cónyuge', $filas[0]->parentesco);
        $this->assertSame('Pedro Pérez', $filas[1]->nombreCompleto);
        $this->assertSame('Hijo', $filas[1]->parentesco);
    }

    /** HU-04 criterio 3: el administrador modifica el parentesco u otro dato y el sistema lo actualiza. */
    public function test_edita_los_datos_de_un_familiar(): void
    {
        $id = $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'Maria', 'Lopez', 'hijo');

        (new EditarFamiliar($this->familiares, $this->personas))->ejecutar($id, 'María José', 'López', 'hija');

        $fila = $this->listarFamiliares->ejecutar(self::EMPLEADO_ID)[0];
        $this->assertSame('María José López', $fila->nombreCompleto);
        $this->assertSame('Hija', $fila->parentesco);
    }

    /** HU-04 criterio 4: el parentesco solo puede ser cónyuge, hijo o hija. */
    public function test_solo_se_permite_conyuge_hijo_o_hija(): void
    {
        $id = $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'María', 'López', 'conyuge');
        $editar = new EditarFamiliar($this->familiares, $this->personas);

        foreach (['primo', 'madre', '', 'HIJO '] as $invalido) {
            $alRegistrar = $this->mensajeDeError(
                fn () => $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'Pedro', 'Pérez', $invalido)
            );
            $alEditar = $this->mensajeDeError(fn () => $editar->ejecutar($id, 'María', 'López', $invalido));

            $this->assertSame('El parentesco solo puede ser cónyuge, hijo o hija.', $alRegistrar);
            $this->assertSame('El parentesco solo puede ser cónyuge, hijo o hija.', $alEditar);
        }
    }

    /** HU-04 criterio 5: nombres y apellidos del familiar solo admiten letras y espacios. */
    public function test_rechaza_nombres_de_familiar_con_numeros_o_simbolos(): void
    {
        foreach (['Ma3ría', 'M', 'María#'] as $invalido) {
            $mensaje = $this->mensajeDeError(
                fn () => $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, $invalido, 'López', 'hija')
            );
            $this->assertSame('El campo nombres solo admite letras y espacios, de 2 a 100 caracteres.', $mensaje);
        }
        $this->assertSame(0, count($this->listarFamiliares->ejecutar(self::EMPLEADO_ID)));
    }

    /** HU-04 criterio 1: el familiar solo se vincula a un empleado, no a un administrador. */
    public function test_no_se_vincula_un_familiar_a_un_administrador(): void
    {
        $mensaje = $this->mensajeDeError(
            fn () => $this->registrarFamiliar->ejecutar(1, 'María', 'López', 'conyuge')
        );

        $this->assertSame('El familiar debe vincularse a un empleado registrado.', $mensaje);
    }

    /** HU-07 criterio 1: al confirmar, el familiar se elimina y deja de mostrarse al empleado. */
    public function test_al_eliminar_el_familiar_deja_de_mostrarse(): void
    {
        $id = $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'María', 'López', 'conyuge');

        $eliminado = (new EliminarFamiliar($this->familiares))->ejecutar($id, true);

        $this->assertTrue($eliminado);
        $this->assertSame(0, count($this->listarFamiliares->ejecutar(self::EMPLEADO_ID)));
        // El registro se conserva como "Eliminado" para no perder su consumo anterior.
        $this->assertSame('Eliminado', $this->familiares->buscarPorId($id)->getEstado()->value);
    }

    /** HU-07 criterio 3: si el administrador cancela la confirmación, el familiar se mantiene. */
    public function test_si_se_cancela_la_confirmacion_no_se_elimina(): void
    {
        $id = $this->registrarFamiliar->ejecutar(self::EMPLEADO_ID, 'María', 'López', 'conyuge');

        $eliminado = (new EliminarFamiliar($this->familiares))->ejecutar($id, false);

        $this->assertFalse($eliminado);
        $this->assertSame(1, count($this->listarFamiliares->ejecutar(self::EMPLEADO_ID)));
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