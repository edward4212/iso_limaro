<?php
declare(strict_types=1);

session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Rutas base
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Autoload muy simple (puedes cambiarlo por Composer)
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/core/' . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Config
require APP_PATH . '/config/config.php';
require APP_PATH . '/config/database.php';

// Router
$router = new Router();

// Ruta por defecto: dashboard/index
$route = isset($_GET['url']) && $_GET['url'] !== ''
    ? $_GET['url']
    : 'dashboard/index';

$router->dispatch($route);
