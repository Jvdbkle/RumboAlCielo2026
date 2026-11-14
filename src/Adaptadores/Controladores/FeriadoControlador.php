<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\ListarFeriados;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFeriado;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-15: pantallas del administrador para registrar feriados y días sin atención.
 * Reciben el formulario y llaman a los casos de uso. Sin reglas de negocio.
 */
final class FeriadoControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private ListarFeriados $listarFeriados,
        private RegistrarFeriado $registrarFeriado,
    ) {
    }

    public function listar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->render('feriados_lista', [
            'feriados' => $this->listarFeriados->ejecutar(),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    /** HU-15 criterio 1: formulario con la fecha y el motivo. */
    public function mostrarNuevo(): void
    {
        $this->guardia->exigirAdministrador();
        $this->mostrarFormulario(['fecha' => '', 'motivo' => ''], '');
    }

    public function registrar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $valores = ['fecha' => (string) ($_POST['fecha'] ?? ''), 'motivo' => (string) ($_POST['motivo'] ?? '')];

        try {
            $this->registrarFeriado->ejecutar($valores['fecha'], $valores['motivo']);
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario($valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'El día sin atención fue registrado.');
        $this->redirigir('/administrador/feriados');
    }

    /** @param array{fecha: string, motivo: string} $valores */
    private function mostrarFormulario(array $valores, string $error): void
    {
        $this->render('feriado_formulario', ['valores' => $valores, 'error' => $error]);
    }
}