<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\CasosDeUso\ArchivoExportado;
use RumboAlCielo\Aplicacion\CasosDeUso\CerrarLiquidacion;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarLiquidacion;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarTablero;
use RumboAlCielo\Aplicacion\CasosDeUso\EmitirListaDiaria;
use RumboAlCielo\Aplicacion\CasosDeUso\ExportarPlanilla;
use RumboAlCielo\Aplicacion\CasosDeUso\ExportarReservas;
use RumboAlCielo\Aplicacion\CasosDeUso\GenerarLiquidacion;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-23 a HU-27: pantallas de reportes del administrador (tablero, lista del día, exportar
 * reservas y liquidación mensual con su planilla). Sin reglas de negocio.
 */
final class ReporteControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private ConsultarTablero $consultarTablero,
        private EmitirListaDiaria $emitirLista,
        private ExportarReservas $exportarReservas,
        private GenerarLiquidacion $generarLiquidacion,
        private CerrarLiquidacion $cerrarLiquidacion,
        private ConsultarLiquidacion $consultarLiquidacion,
        private ExportarPlanilla $exportarPlanilla,
    ) {
    }

    /** HU-23. */
    public function tablero(): void
    {
        $this->guardia->exigirAdministrador();
        $this->render('tablero', ['tablero' => $this->consultarTablero->ejecutar(), 'hoy' => Reloj::ahora()]);
    }

    /** HU-24. */
    public function listaDiaria(): void
    {
        $this->guardia->exigirAdministrador();
        $lista = null;
        $error = '';
        try {
            $lista = $this->emitirLista->ejecutar();
        } catch (ReglaDeNegocioException $excepcion) {
            $error = $excepcion->getMessage();
        }
        $this->render('lista_diaria', ['lista' => $lista, 'error' => $error]);
    }

    /** HU-25: sin "exportar" muestra el formulario; con él descarga el archivo o explica por qué no. */
    public function exportarReservas(): void
    {
        $this->guardia->exigirAdministrador();
        $desde = $this->fecha($_GET['desde'] ?? null);
        $hasta = $this->fecha($_GET['hasta'] ?? null);
        $formato = (string) ($_GET['formato'] ?? ExportarReservas::FORMATO_CSV);
        $error = '';

        if (isset($_GET['exportar'])) {
            try {
                $this->descargar($this->exportarReservas->ejecutar($desde, $hasta, $formato));
            } catch (ReglaDeNegocioException $excepcion) {
                $error = $excepcion->getMessage();
            }
        }

        $this->render('exportar_reservas', ['desde' => $desde, 'hasta' => $hasta, 'formato' => $formato, 'error' => $error]);
    }

    /** HU-26: la liquidación del mes elegido, con sus acciones. */
    public function liquidacion(): void
    {
        $this->guardia->exigirAdministrador();
        $mes = $this->mes($_GET['mes'] ?? null);
        $this->render('liquidacion', [
            'mes' => $mes,
            'anterior' => $mes->modify('-1 month'),
            'siguiente' => $mes->modify('+1 month'),
            'resultado' => $this->consultarLiquidacion->ejecutar($mes),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    public function generar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $mes = $this->mes($_POST['mes'] ?? null);
        try {
            $this->generarLiquidacion->ejecutar($mes);
            $this->avisar('ok', 'La liquidación del mes fue generada.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/liquidacion?mes=' . $mes->format('Y-m'));
    }

    public function cerrar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $mes = $this->mes($_POST['mes'] ?? null);
        try {
            $this->cerrarLiquidacion->ejecutar($mes);
            $this->avisar('ok', 'El mes fue cerrado. Ya puedes exportar la planilla.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/liquidacion?mes=' . $mes->format('Y-m'));
    }

    /** HU-27. */
    public function planilla(): void
    {
        $this->guardia->exigirAdministrador();
        $mes = $this->mes($_GET['mes'] ?? null);
        try {
            $this->descargar($this->exportarPlanilla->ejecutar($mes));
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
            $this->redirigir('/administrador/liquidacion?mes=' . $mes->format('Y-m'));
        }
    }

    private function descargar(ArchivoExportado $archivo): never
    {
        header('Content-Type: ' . $archivo->tipoMime);
        header('Content-Disposition: attachment; filename="' . $archivo->nombre . '"');
        header('Content-Length: ' . strlen($archivo->contenido));
        echo $archivo->contenido;
        exit;
    }

    private function fecha(mixed $texto): ?DateTimeImmutable
    {
        $texto = trim((string) $texto);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        return $fecha !== false && $fecha->format('Y-m-d') === $texto ? $fecha : null;
    }

    private function mes(mixed $texto): DateTimeImmutable
    {
        $mes = DateTimeImmutable::createFromFormat('!Y-m', trim((string) $texto));
        return $mes !== false ? $mes : Reloj::ahora()->modify('first day of this month')->setTime(0, 0);
    }
}