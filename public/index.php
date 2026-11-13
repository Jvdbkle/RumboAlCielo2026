<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RumboAlCielo\Adaptadores\Controladores\AutenticacionControlador;
use RumboAlCielo\Adaptadores\Controladores\Guardia;
use RumboAlCielo\Adaptadores\Controladores\PanelControlador;
use RumboAlCielo\Adaptadores\Controladores\UsuarioControlador;
use RumboAlCielo\Adaptadores\Repositorios\PersonaRepositorioMySql;
use RumboAlCielo\Adaptadores\Repositorios\RolRepositorioMySql;
use RumboAlCielo\Adaptadores\Repositorios\UsuarioRepositorioMySql;
use RumboAlCielo\Aplicacion\CasosDeUso\CambiarContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\CerrarSesion;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\DeshabilitarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarUsuarios;
use RumboAlCielo\Aplicacion\CasosDeUso\ReestablecerContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\ReincorporarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\VerificarSesion;
use RumboAlCielo\Infraestructura\BaseDeDatos\Conexion;
use RumboAlCielo\Infraestructura\Sesion\SesionPhp;
use RumboAlCielo\Adaptadores\Controladores\FamiliarControlador;
use RumboAlCielo\Adaptadores\Repositorios\FamiliarRepositorioMySql;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\EliminarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFamiliares;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;


// Cookie de sesión segura: no accesible desde JavaScript y no se envía desde otros sitios.
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Se arman las piezas: la conexión, los repositorios, los casos de uso y los controladores.
$pdo = Conexion::obtener();
$usuarios = new UsuarioRepositorioMySql($pdo);
$personas = new PersonaRepositorioMySql($pdo);
$roles = new RolRepositorioMySql($pdo);
$familiares = new FamiliarRepositorioMySql($pdo);
$sesion = new SesionPhp();

$guardia = new Guardia(new VerificarSesion($sesion), $sesion, $roles);
$autenticacion = new AutenticacionControlador(
    new IniciarSesion($usuarios, $roles),
    new CambiarContrasenia($usuarios),
    new CerrarSesion($sesion),
    $sesion,
    $guardia,
);
$paneles = new PanelControlador($guardia);
$gestionUsuarios = new UsuarioControlador(
    $guardia,
    $sesion,
    $roles,
    new ListarUsuarios($usuarios, $personas, $roles),
    new RegistrarUsuario($usuarios, $personas, $roles),
    new ConsultarUsuario($usuarios, $personas),
    new EditarUsuario($usuarios, $personas),
    new DeshabilitarUsuario($usuarios),
    new ReincorporarUsuario($usuarios),
    new ReestablecerContrasenia($usuarios, $roles),
);

$gestionFamiliares = new FamiliarControlador(
    $guardia,
    new ConsultarUsuario($usuarios, $personas),
    new ListarFamiliares($familiares, $personas),
    new RegistrarFamiliar($familiares, $personas, $usuarios, $roles),
    new ConsultarFamiliar($familiares, $personas),
    new EditarFamiliar($familiares, $personas),
    new EliminarFamiliar($familiares),
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
    case 'POST /cerrar-sesion':
        $autenticacion->cerrarSesion();
        break;
    case 'GET /administrador':
        $paneles->mostrarAdministrador();
        break;
    case 'GET /empleado':
        $paneles->mostrarEmpleado();
        break;
    case 'GET /administrador/usuarios':
        $gestionUsuarios->listar();
        break;
    case 'GET /administrador/usuarios/nuevo':
        $gestionUsuarios->mostrarNuevo();
        break;
    case 'POST /administrador/usuarios/nuevo':
        $gestionUsuarios->registrar();
        break;
    case 'GET /administrador/usuarios/editar':
        $gestionUsuarios->mostrarEdicion();
        break;
    case 'POST /administrador/usuarios/editar':
        $gestionUsuarios->editar();
        break;
    case 'POST /administrador/usuarios/deshabilitar':
        $gestionUsuarios->deshabilitar();
        break;
    case 'POST /administrador/usuarios/reincorporar':
        $gestionUsuarios->reincorporar();
        break;
    case 'POST /administrador/usuarios/restablecer':
        $gestionUsuarios->restablecer();
        break;
            case 'GET /administrador/familiares':
        $gestionFamiliares->listar();
        break;
    case 'GET /administrador/familiares/nuevo':
        $gestionFamiliares->mostrarNuevo();
        break;
    case 'POST /administrador/familiares/nuevo':
        $gestionFamiliares->registrar();
        break;
    case 'GET /administrador/familiares/editar':
        $gestionFamiliares->mostrarEdicion();
        break;
    case 'POST /administrador/familiares/editar':
        $gestionFamiliares->editar();
        break;
    case 'GET /administrador/familiares/eliminar':
        $gestionFamiliares->mostrarConfirmacion();
        break;
    case 'POST /administrador/familiares/eliminar':
        $gestionFamiliares->eliminar();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
}