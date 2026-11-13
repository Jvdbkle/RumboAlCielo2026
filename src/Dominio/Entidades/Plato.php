<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\RegistraEventos;

final class Plato
{
    use RegistraEventos;

    public function __construct(
        private int $idPlato,
        private int $idMenu,
        private DateTimeImmutable $fecha,
        private string $sopa,
        private string $platoPrincipal,
        private string $ensaladaGuarnicion,
        private string $postre,
        private string $refresco,
        private string $fotoSopa,
        private string $fotoPlatoPrincipal,
    ) {
    }

    public function getIdPlato(): int
    {
        return $this->idPlato;
    }

    public function getIdMenu(): int
    {
        return $this->idMenu;
    }

    public function getFecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getSopa(): string
    {
        return $this->sopa;
    }

    public function getPlatoPrincipal(): string
    {
        return $this->platoPrincipal;
    }

    public function getEnsaladaGuarnicion(): string
    {
        return $this->ensaladaGuarnicion;
    }

    public function getPostre(): string
    {
        return $this->postre;
    }

    public function getRefresco(): string
    {
        return $this->refresco;
    }

    public function getFotoSopa(): string
    {
        return $this->fotoSopa;
    }

    public function getFotoPlatoPrincipal(): string
    {
        return $this->fotoPlatoPrincipal;
    }

    /**
     * Cambia los componentes. Una foto nueva inválida se rechaza y se conserva la anterior
     * (HU-12 criterio 2). Con null se conserva la foto actual.
     */
    public function cambiarComponentes(
        string $sopa,
        string $platoPrincipal,
        string $ensaladaGuarnicion,
        string $postre,
        string $refresco,
        ?string $fotoSopa = null,
        ?string $fotoPlatoPrincipal = null,
    ): void {
        foreach ([$fotoSopa, $fotoPlatoPrincipal] as $foto) {
            if ($foto !== null && !$this->validarFoto($foto)) {
                throw new ReglaDeNegocioException('La foto debe ser JPG o PNG.');
            }
        }
        $this->sopa = $sopa;
        $this->platoPrincipal = $platoPrincipal;
        $this->ensaladaGuarnicion = $ensaladaGuarnicion;
        $this->postre = $postre;
        $this->refresco = $refresco;
        $this->fotoSopa = $fotoSopa ?? $this->fotoSopa;
        $this->fotoPlatoPrincipal = $fotoPlatoPrincipal ?? $this->fotoPlatoPrincipal;
    }

    /** HU-12 criterio 1: componentes completos y fotos JPG/PNG. */
    public function registrar(): void
    {
        $this->verificarCompleto();
    }

    /** HU-12 criterio 3: el plato editado debe seguir completo y válido. */
    public function editar(): void
    {
        $this->verificarCompleto();
    }

    /** HU-12 criterio 2: solo JPG o PNG. */
    public function validarFoto(string $archivo): bool
    {
        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png'], true);
    }

    /** HU-12 criterio 3: avisa a los empleados con reserva en esa fecha (lo despacha la aplicación). */
    public function notificarCambio(): void
    {
        $this->registrarEvento('plato.modificado', [
            'idPlato' => $this->idPlato,
            'fecha' => $this->fecha->format('Y-m-d'),
        ]);
    }

    private function verificarCompleto(): void
    {
        $componentes = [$this->sopa, $this->platoPrincipal, $this->ensaladaGuarnicion, $this->postre, $this->refresco];
        foreach ($componentes as $componente) {
            if (trim($componente) === '') {
                throw new ReglaDeNegocioException('Faltan componentes del plato: sopa, plato principal, ensalada o guarnición, postre y refresco.');
            }
        }
        if (!$this->validarFoto($this->fotoSopa) || !$this->validarFoto($this->fotoPlatoPrincipal)) {
            throw new ReglaDeNegocioException('Las fotos de la sopa y del plato principal deben ser JPG o PNG.');
        }
    }
}