<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RumboAlCielo\Adaptadores\Controladores\AutenticacionControlador;
use RumboAlCielo\Adaptadores\Repositorios\RolRepositorioMySql;
use RumboAlCielo\Adaptadores\Repositorios\UsuarioRepositorioMySql;
use RumboAlCielo\Aplicacion\CasosDeUso\CambiarContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Infraestructura\BaseDeDatos\Conexion;

session_start();

// Se arman las piezas: la conexión, los repositorios, los casos de uso y el controlador.
$pdo = Conexion::obtener();
$usuarios = new UsuarioRepositorioMySql($pdo);
$autenticacion = new AutenticacionControlador(
    new IniciarSesion($usuarios, new RolRepositorioMySql($pdo)),
    new CambiarContrasenia($usuarios),
);

// Se lee qué página pidió el navegador y se la dirige a su controlador.
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo . ' ' . $ruta) {
    case 'GET /':
    case 'GET /login':
        $autenticacion->mostrarLogin();
        break;
    case 'POST /login':
        $autenticacion->iniciarSesion();
        break;
    case 'GET /cambiar-contrasenia':
        $autenticacion->mostrarCambioContrasenia();
        break;
    case 'POST /cambiar-contrasenia':
        $autenticacion->cambiarContrasenia();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
}