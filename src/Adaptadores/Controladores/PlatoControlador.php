<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Adaptadores\Archivos\ArchivosSubidosPhp;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarSemanaDelMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\DiaMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarPlato;
use RumboAlCielo\Aplicacion\CasosDeUso\PublicarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarPlato;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-12 y HU-13: pantallas del administrador para registrar y editar el plato de cada día
 * y publicar el menú de la semana. Reciben los formularios y llaman a los casos de uso.
 */
final class PlatoControlador extends ControladorBase
{
    private const CAMPOS = ['sopa', 'platoPrincipal', 'ensaladaGuarnicion', 'postre', 'refresco'];

    public function __construct(
        private Guardia $guardia,
        private ConsultarSemanaDelMenu $consultarSemana,
        private RegistrarPlato $registrarPlato,
        private EditarPlato $editarPlato,
        private PublicarMenu $publicarMenu,
    ) {
    }

    /** HU-13 criterio 1: los cuatro días de la semana con su plato. */
    public function semana(): void
    {
        $this->guardia->exigirAdministrador();
        $resultado = $this->consultarSemana->ejecutar(self::fecha($_GET['semana'] ?? ''));

        $this->render('menu_admin', [
            'semana' => $resultado,
            'anterior' => $resultado->lunes->modify('-7 day'),
            'siguiente' => $resultado->lunes->modify('+7 day'),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    /** HU-12 criterio 1: formulario para registrar el plato de un día. */
    public function mostrarNuevo(): void
    {
        $this->guardia->exigirAdministrador();
        $fecha = self::fecha($_GET['fecha'] ?? '');
        if ($fecha === null) {
            $this->redirigir('/administrador/menu');
        }
        $this->mostrarFormulario('Registrar plato', '/administrador/platos/nuevo', $fecha, false, [], '');
    }

    public function registrar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $fecha = (string) ($_POST['fecha'] ?? '');
        $valores = $this->valoresDelFormulario();

        try {
            $this->registrarPlato->ejecutar(
                $fecha,
                $valores['sopa'],
                $valores['platoPrincipal'],
                $valores['ensaladaGuarnicion'],
                $valores['postre'],
                $valores['refresco'],
                ArchivosSubidosPhp::obligatorio('fotoSopa'),
                ArchivosSubidosPhp::obligatorio('fotoPlatoPrincipal'),
            );
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario('Registrar plato', '/administrador/platos/nuevo', self::fecha($fecha) ?? new DateTimeImmutable('today'), false, $valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'El plato fue registrado.');
        $this->redirigir('/administrador/menu?semana=' . urlencode($fecha));
    }

    /** HU-12 criterio 3: formulario con el plato actual. */
    public function mostrarEdicion(): void
    {
        $this->guardia->exigirAdministrador();
        $fecha = self::fecha($_GET['fecha'] ?? '');
        $plato = $fecha === null ? null : $this->platoDelDia($fecha);
        if ($fecha === null || $plato === null) {
            $this->avisar('error', 'No hay un plato registrado para ese día.');
            $this->redirigir('/administrador/menu');
        }

        $this->mostrarFormulario('Editar plato', '/administrador/platos/editar', $fecha, true, [
            'sopa' => $plato->sopa,
            'platoPrincipal' => $plato->platoPrincipal,
            'ensaladaGuarnicion' => $plato->ensaladaGuarnicion,
            'postre' => $plato->postre,
            'refresco' => $plato->refresco,
        ], '');
    }

    public function editar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $fecha = (string) ($_POST['fecha'] ?? '');
        $valores = $this->valoresDelFormulario();

        try {
            $this->editarPlato->ejecutar(
                $fecha,
                $valores['sopa'],
                $valores['platoPrincipal'],
                $valores['ensaladaGuarnicion'],
                $valores['postre'],
                $valores['refresco'],
                ArchivosSubidosPhp::opcional('fotoSopa'),
                ArchivosSubidosPhp::opcional('fotoPlatoPrincipal'),
            );
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario('Editar plato', '/administrador/platos/editar', self::fecha($fecha) ?? new DateTimeImmutable('today'), true, $valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'El plato fue actualizado.');
        $this->redirigir('/administrador/menu?semana=' . urlencode($fecha));
    }

    /** HU-13 criterios 1 y 3: publica la semana, salvo que haya un día feriado. */
    public function publicar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $semana = (string) ($_POST['semana'] ?? '');

        try {
            $this->publicarMenu->ejecutar($semana);
            $this->avisar('ok', 'El menú de la semana fue publicado.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/menu?semana=' . urlencode($semana));
    }

    private function platoDelDia(DateTimeImmutable $fecha): ?DiaMenu
    {
        foreach ($this->consultarSemana->ejecutar($fecha)->dias as $dia) {
            if ($dia->fecha->format('Y-m-d') === $fecha->format('Y-m-d')) {
                return $dia->plato;
            }
        }
        return null;
    }

    /** Convierte un texto AAAA-MM-DD en fecha; null si no es una fecha válida. */
    private static function fecha(mixed $texto): ?DateTimeImmutable
    {
        $texto = is_string($texto) ? trim($texto) : '';
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        return $fecha !== false && $fecha->format('Y-m-d') === $texto ? $fecha : null;
    }

    /** @return array<string, string> */
    private function valoresDelFormulario(): array
    {
        $valores = [];
        foreach (self::CAMPOS as $campo) {
            $valores[$campo] = (string) ($_POST[$campo] ?? '');
        }
        return $valores;
    }

    /** @param array<string, string> $valores */
    private function mostrarFormulario(string $titulo, string $accion, DateTimeImmutable $fecha, bool $esEdicion, array $valores, string $error): void
    {
        $this->render('plato_formulario', [
            'titulo' => $titulo,
            'accion' => $accion,
            'fecha' => $fecha,
            'esEdicion' => $esEdicion,
            'valores' => $valores + array_fill_keys(self::CAMPOS, ''),
            'error' => $error,
        ]);
    }
}