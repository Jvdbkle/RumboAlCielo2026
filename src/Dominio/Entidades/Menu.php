<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoMenu;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class Menu
{
    /** @var array<string, Plato> Un plato por día, indexado por fecha Y-m-d. */
    private array $platos = [];

    public function __construct(
        private int $idMenu,
        private DateTimeImmutable $fechaInicio,
        private EstadoMenu $estado = EstadoMenu::Pendiente,
    ) {
        if ($fechaInicio->format('N') !== '1') {
            throw new ReglaDeNegocioException('El menú semanal debe iniciar un lunes.');
        }
    }

    public function getIdMenu(): int
    {
        return $this->idMenu;
    }

    public function getFechaInicio(): DateTimeImmutable
    {
        return $this->fechaInicio;
    }

    public function getEstado(): EstadoMenu
    {
        return $this->estado;
    }

    /** @return list<Plato> */
    public function getPlatos(): array
    {
        return array_values($this->platos);
    }

    /** HU-13 criterio 2: solo de lunes a jueves de la semana del menú. */
    public function asignarPlato(Plato $plato): void
    {
        $dia = $plato->getFecha()->setTime(0, 0);
        $dias = (int) $this->fechaInicio->setTime(0, 0)->diff($dia)->format('%R%a');
        if ($dias < 0 || $dias > 3) {
            throw new ReglaDeNegocioException('El menú solo permite días de lunes a jueves de la semana indicada.');
        }
        $clave = $dia->format('Y-m-d');
        if (isset($this->platos[$clave])) {
            throw new ReglaDeNegocioException('Ya hay un plato asignado para ese día.');
        }
        $this->platos[$clave] = $plato;
    }

    /** HU-13 criterio 1: publica la semana. */
    public function publicar(): void
    {
        if ($this->platos === []) {
            throw new ReglaDeNegocioException('No se puede publicar un menú sin platos asignados.');
        }
        $this->estado = EstadoMenu::Publicado;
    }

    public function estaPublicado(): bool
    {
        return $this->estado === EstadoMenu::Publicado;
    }

    /** HU-14 criterio 1: el plato del día, o null si ese día no tiene. */
    public function consultarDia(DateTimeImmutable $fecha): ?Plato
    {
        return $this->platos[$fecha->format('Y-m-d')] ?? null;
    }
}