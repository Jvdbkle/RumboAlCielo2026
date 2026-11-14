<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFeriado;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Tests\Dobles\FeriadoRepositorioEnMemoria;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFeriados;


/** HU-15 Registrar feriados y días sin atención. */
final class RegistrarFeriadoTest extends TestCase
{
    private FeriadoRepositorioEnMemoria $feriados;
    private RegistrarFeriado $registrarFeriado;

    protected function setUp(): void
    {
        $this->feriados = new FeriadoRepositorioEnMemoria();
        $this->registrarFeriado = new RegistrarFeriado($this->feriados);
    }

    /** HU-15 criterio 1: la fecha y el motivo se registran como día sin atención. */
    public function test_registra_la_fecha_como_dia_sin_atencion(): void
    {
        $this->registrarFeriado->ejecutar('2026-11-19', 'Día de la Patria');

        $feriado = $this->feriados->buscarPorFecha(new DateTimeImmutable('2026-11-19'));
        $this->assertSame('Día de la Patria', $feriado->getMotivo());
        $this->assertSame(null, $this->feriados->buscarPorFecha(new DateTimeImmutable('2026-11-20')));
    }

    /** HU-15 criterio 2: una fecha ya registrada no se guarda y se indica el motivo. */
    public function test_rechaza_una_fecha_ya_registrada(): void
    {
        $this->registrarFeriado->ejecutar('2026-11-19', 'Día de la Patria');

        $mensaje = $this->mensajeDeError(fn () => $this->registrarFeriado->ejecutar('2026-11-19', 'Otro motivo'));

        $this->assertSame('Esa fecha ya está registrada como día sin atención.', $mensaje);
        $this->assertSame(1, count($this->feriados->listarTodos()));
    }

    /** HU-15 criterio 2: una fecha inválida no se guarda y se indica el motivo. */
    public function test_rechaza_una_fecha_invalida(): void
    {
        foreach (['2026-02-30', '19/11/2026', 'mañana', ''] as $invalida) {
            $mensaje = $this->mensajeDeError(fn () => $this->registrarFeriado->ejecutar($invalida, 'Motivo válido'));
            $this->assertSame('La fecha ingresada no es válida.', $mensaje);
        }
        $this->assertSame(0, count($this->feriados->listarTodos()));
    }

    /** HU-15 criterio 1: el motivo es obligatorio y tiene entre 3 y 100 caracteres. */
    public function test_el_motivo_es_obligatorio_y_de_3_a_100_caracteres(): void
    {
        $vacio = $this->mensajeDeError(fn () => $this->registrarFeriado->ejecutar('2026-11-19', '   '));
        $this->assertSame('Debe indicar el motivo del feriado o día sin atención.', $vacio);

        foreach (['ab', str_repeat('a', 101)] as $invalido) {
            $mensaje = $this->mensajeDeError(fn () => $this->registrarFeriado->ejecutar('2026-11-19', $invalido));
            $this->assertSame('El motivo debe tener entre 3 y 100 caracteres.', $mensaje);
        }
        $this->assertSame(0, count($this->feriados->listarTodos()));
    }
    public function test_lista_los_dias_sin_atencion_ordenados_por_fecha(): void
    {
        $this->registrarFeriado->ejecutar('2026-12-25', 'Navidad');
        $this->registrarFeriado->ejecutar('2026-11-19', 'Día de la Patria');

        $lista = (new ListarFeriados($this->feriados))->ejecutar();

        $this->assertSame(2, count($lista));
        $this->assertSame('Día de la Patria', $lista[0]->getMotivo());
        $this->assertSame('Navidad', $lista[1]->getMotivo());
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