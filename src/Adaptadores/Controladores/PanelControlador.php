<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarSemanaDelMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarUsuario;
use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * Pantallas de inicio de cada rol (HU-01 criterio 1). Cada una exige su rol y muestra un
 * resumen con datos que ya existen: el menú de la semana y los accesos a cada módulo.
 */
final class PanelControlador extends ControladorBase
{
    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    private const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function __construct(
        private Guardia $guardia,
        private Sesion $sesion,
        private ConsultarUsuario $consultarUsuario,
        private ConsultarSemanaDelMenu $consultarSemana,
        private ConsultarMenu $consultarMenu,
    ) {
    }

    public function mostrarAdministrador(): void
    {
        $this->guardia->exigirAdministrador();
        $semana = $this->consultarSemana->ejecutar();
        $hoy = Reloj::ahora();

        $registrados = 0;
        $sinAtencion = 0;
        $galeria = [];
                foreach ($semana->dias as $dia) {
            if ($dia->motivoFeriado !== null) {
                $sinAtencion++;
                continue;
            }
            if ($dia->plato !== null) {
                $registrados++;
                $galeria[] = $this->tarjetaDelDia($dia->fecha, $dia->plato->sopa, $dia->plato->platoPrincipal, $dia->plato->fotoSopa, $dia->plato->fotoPlatoPrincipal, $hoy);
            }
        }

        $this->render('panel', $this->datosComunes($hoy, 'Panel de administración', 'Administrador') + [
            'mensaje' => 'Gestiona usuarios, familiares, el menú y los días sin atención desde un solo lugar.',
            'estadisticas' => [
                ['Platos registrados', $registrados . ' de ' . (4 - $sinAtencion), 'naranja'],                ['Menú de la semana', $semana->publicado ? 'Publicado' : 'Sin publicar', $semana->publicado ? 'verde' : 'amarillo'],
                ['Días sin atención', (string) $sinAtencion, 'azul'],
            ],
            'galeriaTitulo' => 'Platos de esta semana',
            'galeriaVacia' => 'Todavía no registraste platos para esta semana.',
            'galeria' => $galeria,
            'galeriaRuta' => '/administrador/menu',
            'galeriaEnlace' => 'Ir al menú semanal',
            'modulos' => [
                ['titulo' => 'Usuarios', 'descripcion' => 'Registrar, editar, deshabilitar y reincorporar usuarios.', 'ruta' => '/administrador/usuarios'],
                ['titulo' => 'Menú semanal', 'descripcion' => 'Registrar los platos de cada día y publicar el menú de la semana.', 'ruta' => '/administrador/menu'],
                ['titulo' => 'Feriados', 'descripcion' => 'Registrar los días sin atención del almuerzo.', 'ruta' => '/administrador/feriados'],
                ['titulo' => 'Pensiones', 'descripcion' => 'Confirmar, rechazar o revocar las pensiones mensuales.', 'ruta' => '/administrador/pensiones'],
                ['titulo' => 'Reservas', 'descripcion' => 'Consultar las reservas por fecha, rango o empleado.', 'ruta' => '/administrador/reservas'],
                ['titulo' => 'Mostrador de entrega', 'descripcion' => 'Validar el código QR y registrar la entrega del almuerzo.', 'ruta' => '/administrador/mostrador'],
                ['titulo' => 'Tablero del día', 'descripcion' => 'Ver los indicadores del servicio de hoy de un vistazo.', 'ruta' => '/administrador/tablero'],
                ['titulo' => 'Lista del día', 'descripcion' => 'Imprimir la lista de almuerzos con casillas de control.', 'ruta' => '/administrador/lista-diaria'],
                ['titulo' => 'Exportar reservas', 'descripcion' => 'Descargar las reservas de un rango en CSV o Excel.', 'ruta' => '/administrador/reservas/exportar'],
                ['titulo' => 'Liquidación mensual', 'descripcion' => 'Calcular el descuento por empleado y exportar la planilla.', 'ruta' => '/administrador/liquidacion'],
               ],
        ]);
    }

    public function mostrarEmpleado(): void
    {
        $this->guardia->exigirEmpleado();
        $menu = $this->consultarMenu->ejecutar();
        $hoy = Reloj::ahora();

        $galeria = [];
        foreach ($menu->dias as $dia) {
            $galeria[] = $this->tarjetaDelDia($dia->fecha, $dia->sopa, $dia->platoPrincipal, $dia->fotoSopa, $dia->fotoPlatoPrincipal, $hoy);
        }

        $this->render('panel', $this->datosComunes($hoy, 'Bienvenido a Rumbo al Cielo', 'Empleado') + [
            'mensaje' => 'Mira qué se sirve esta semana y planifica tus almuerzos.',
            'estadisticas' => [],
            'galeriaTitulo' => 'Menú de esta semana',
            'galeriaVacia' => $menu->aviso !== '' ? $menu->aviso : 'El menú de esta semana todavía no fue publicado.',
            'galeria' => $galeria,
            'galeriaRuta' => '/empleado/menu',
            'galeriaEnlace' => 'Ver el menú completo',
            'modulos' => [
                ['titulo' => 'Menú de la semana', 'descripcion' => 'Ver el menú de cada día con sus fotos.', 'ruta' => '/empleado/menu'],
                ['titulo' => 'Mis pensiones', 'descripcion' => 'Solicitar la pensión mensual y ver su estado.', 'ruta' => '/empleado/pensiones'],
                ['titulo' => 'Mis reservas', 'descripcion' => 'Reservar y cancelar tus almuerzos y los de tus familiares.', 'ruta' => '/empleado/reservas'],
                ['titulo' => 'Mi código QR', 'descripcion' => 'Mostrar el código para retirar tu almuerzo de hoy.', 'ruta' => '/empleado/qr'],
                ],
        ]);
    }

    /** @return array<string, mixed> */
    private function datosComunes(DateTimeImmutable $hoy, string $titulo, string $rol): array
    {
        return [
            'titulo' => $titulo,
            'rol' => $rol,
            'saludo' => 'Hola, ' . $this->primerNombre() . '.',
            'fechaHoy' => self::DIAS[(int) $hoy->format('N')] . ' ' . $hoy->format('j') . ' de ' . self::MESES[(int) $hoy->format('n')] . ' de ' . $hoy->format('Y'),
        ];
    }

    /** @return array<string, mixed> */
    private function tarjetaDelDia(DateTimeImmutable $fecha, string $sopa, string $principal, string $fotoSopa, string $fotoPrincipal, DateTimeImmutable $hoy): array
    {
        return [
            'dia' => self::DIAS[(int) $fecha->format('N')],
            'fecha' => $fecha->format('d/m'),
            'sopa' => $sopa,
            'principal' => $principal,
            'fotoSopa' => $fotoSopa,
            'fotoPrincipal' => $fotoPrincipal,
            'hoy' => $fecha->format('Y-m-d') === $hoy->format('Y-m-d'),
        ];
    }

    private function primerNombre(): string
    {
        try {
            $nombres = $this->consultarUsuario->ejecutar((int) $this->sesion->idUsuario())->nombres;
        } catch (ReglaDeNegocioException) {
            return 'bienvenido';
        }
        return explode(' ', trim($nombres))[0];
    }
}