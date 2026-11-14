<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use DateTimeImmutable;
use PDO;
use PDOException;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Entidades\Plato;
use RumboAlCielo\Dominio\Enumeraciones\EstadoMenu;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class MenuRepositorioMySql implements MenuRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorFechaInicio(DateTimeImmutable $lunes): ?Menu
    {
        $consulta = $this->pdo->prepare('SELECT idMenu, fechaInicio, estado FROM menu WHERE fechaInicio = :fecha');
        $consulta->execute(['fecha' => $lunes->format('Y-m-d')]);
        $fila = $consulta->fetch();
        if ($fila === false) {
            return null;
        }

        $menu = new Menu((int) $fila['idMenu'], new DateTimeImmutable($fila['fechaInicio']), EstadoMenu::from($fila['estado']));

        $platos = $this->pdo->prepare(
            'SELECT idPlato, idMenu, fecha, sopa, platoPrincipal, ensaladaGuarnicion, postre, refresco, fotoSopa, fotoPlatoPrincipal
             FROM plato WHERE idMenu = :idMenu ORDER BY fecha'
        );
        $platos->execute(['idMenu' => $menu->getIdMenu()]);
        foreach ($platos->fetchAll() as $p) {
            $menu->asignarPlato(new Plato(
                (int) $p['idPlato'],
                (int) $p['idMenu'],
                new DateTimeImmutable($p['fecha']),
                $p['sopa'],
                $p['platoPrincipal'],
                $p['ensaladaGuarnicion'],
                $p['postre'],
                $p['refresco'],
                $p['fotoSopa'],
                $p['fotoPlatoPrincipal'],
            ));
        }
        return $menu;
    }

    /** Guarda el menú con sus platos (nuevos o modificados), todo junto o nada. Devuelve el id del menú. */
    public function guardar(Menu $menu): int
    {
        $this->pdo->beginTransaction();
        try {
            $idMenu = $menu->getIdMenu();
            if ($idMenu === 0) {
                $this->pdo->prepare('INSERT INTO menu (fechaInicio, estado) VALUES (:fecha, :estado)')->execute([
                    'fecha' => $menu->getFechaInicio()->format('Y-m-d'),
                    'estado' => $menu->getEstado()->value,
                ]);
                $idMenu = (int) $this->pdo->lastInsertId();
            } else {
                $this->pdo->prepare('UPDATE menu SET estado = :estado WHERE idMenu = :idMenu')->execute([
                    'estado' => $menu->getEstado()->value,
                    'idMenu' => $idMenu,
                ]);
            }

            foreach ($menu->getPlatos() as $plato) {
                $this->guardarPlato($plato, $idMenu);
            }
            $this->pdo->commit();
            return $idMenu;
        } catch (PDOException $error) {
            $this->pdo->rollBack();
            // Segunda barrera: la base de datos no admite dos menús de la misma semana ni dos platos el mismo día.
            if ($error->getCode() === '23000') {
                throw new ReglaDeNegocioException('Ya hay un plato registrado para ese día.');
            }
            throw $error;
        }
    }

    private function guardarPlato(Plato $plato, int $idMenu): void
    {
        $datos = [
            'idMenu' => $idMenu,
            'fecha' => $plato->getFecha()->format('Y-m-d'),
            'sopa' => $plato->getSopa(),
            'platoPrincipal' => $plato->getPlatoPrincipal(),
            'ensaladaGuarnicion' => $plato->getEnsaladaGuarnicion(),
            'postre' => $plato->getPostre(),
            'refresco' => $plato->getRefresco(),
            'fotoSopa' => $plato->getFotoSopa(),
            'fotoPlatoPrincipal' => $plato->getFotoPlatoPrincipal(),
        ];

        if ($plato->getIdPlato() === 0) {
            $this->pdo->prepare(
                'INSERT INTO plato (idMenu, fecha, sopa, platoPrincipal, ensaladaGuarnicion, postre, refresco, fotoSopa, fotoPlatoPrincipal)
                 VALUES (:idMenu, :fecha, :sopa, :platoPrincipal, :ensaladaGuarnicion, :postre, :refresco, :fotoSopa, :fotoPlatoPrincipal)'
            )->execute($datos);
            return;
        }

        $datos['idPlato'] = $plato->getIdPlato();
        $this->pdo->prepare(
            'UPDATE plato SET idMenu = :idMenu, fecha = :fecha, sopa = :sopa, platoPrincipal = :platoPrincipal,
                ensaladaGuarnicion = :ensaladaGuarnicion, postre = :postre, refresco = :refresco,
                fotoSopa = :fotoSopa, fotoPlatoPrincipal = :fotoPlatoPrincipal WHERE idPlato = :idPlato'
        )->execute($datos);
    }
}