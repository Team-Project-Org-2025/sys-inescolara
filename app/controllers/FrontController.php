<?php



namespace SysInescolara\controllers;



use Exception;



class FrontController
{

    private $controllerName;

    private $action;

    private $params = [];

    private array $routes = [
        'catalogo'  => ['controller' => 'public', 'action' => 'catalogo'],
        'servicios' => ['controller' => 'public', 'action' => 'servicios'],
        'nosotros'  => ['controller' => 'public', 'action' => 'nosotros'],
        'contacto'  => ['controller' => 'public', 'action' => 'contacto'],
    ];



    public function __construct()
    {

        if (session_status() === PHP_SESSION_NONE) {

            session_start();
        }



        if (!defined('ROOT_PATH')) {

            define('ROOT_PATH', dirname(__DIR__, 2) . '/');
        }



        $this->parseUrl();

        $this->loadController();
    }



    private function parseUrl(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($requestUri, PHP_URL_PATH);

        // Detectar dinámicamente el prefijo de subcarpeta (ej: /sys-inescolara/)
        // usando SCRIPT_NAME que apunta al index.php real
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $basePath = rtrim($basePath, '/');

        // Quitar el prefijo de subcarpeta del URI
        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $segments = array_values(array_filter(explode('/', $uri)));

        if (empty($segments)) {
            $this->controllerName = 'public';
            $this->action = 'home';
            $this->params = [];
        } else {
            $routeKey = $segments[0];
            // Rutas públicas de un solo segmento: catalogo, servicios, nosotros, contacto
            if (isset($this->routes[$routeKey])) {
                $this->controllerName = $this->routes[$routeKey]['controller'];
                $this->action = $this->routes[$routeKey]['action'];
                $this->params = array_slice($segments, 1);
            } else {
                $this->controllerName = $this->sanitize($segments[0]);
                $this->action = $this->sanitize($segments[1] ?? 'index');
                $this->params = array_slice($segments, 2);
            }
        }
    }



    private function loadController(): void
    {

        // Un solo directorio de controladores: cada controlador = un módulo (vista / lógica asociada).

        $controllerFile = ROOT_PATH . "app/controllers/"

            . ucfirst($this->controllerName) . "Controller.php";



        if (!file_exists($controllerFile)) {

            $this->renderNotFound(

                "El controlador '{$this->controllerName}' no existe."

            );

            return;
        }



        require_once $controllerFile;



        if (!function_exists($this->action)) {

            $this->renderNotFound(

                "La función '{$this->action}()' no existe en el controlador '{$this->controllerName}'"

            );

            return;
        }



        try {

            call_user_func_array($this->action, $this->params);
        } catch (Exception $e) {

            $this->renderNotFound("Error interno: " . $e->getMessage());
        }
    }



    private function sanitize(string $input): string
    {

        return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
    }



    private function renderNotFound(string $message, bool $isAjax = false): void
    {

        http_response_code(404);



        $isAjax = $isAjax || (

            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&

            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'

        );



        if ($isAjax) {

            header('Content-Type: application/json');

            echo json_encode(['success' => false, 'message' => $message]);
        } else {

            echo "<!DOCTYPE html>

<html lang='es'>

<head>

    <meta charset='UTF-8'>

    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

    <title>Error 404 | Sys Inescolara</title>

    <link rel=\"shortcut icon\" href=\"" . BASE_URL . "public/assets/icons/Logo - Sys Inescolara.webp\" type=\"image/x-icon\">

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');



        body {

            background-color: #ffffff;

            color: #333333; 

            font-family: 'Montserrat', sans-serif;

            display: flex;

            justify-content: center;

            align-items: center;

            height: 100vh;

            margin: 0;

            flex-direction: column;

            text-align: center;

        }

        .error-container {

            text-align: center;

            padding: 2rem;

        }

        .logo {

            font-size: 2.5rem;

            font-weight: 700;

            letter-spacing: 5px;

            margin-bottom: 2rem;

            text-transform: uppercase;

        }

        h1 { 

            font-size: 8rem; 

            margin: 0; 

            font-weight: 700;

            color: #111111;

        }

        h2 {

            font-size: 1.5rem;

            font-weight: 400;

            margin-top: 0;

        }

        p { 

            font-size: 1.1rem; 

            margin-bottom: 2rem;

        }

        a {

            display: inline-block;

            margin-top: 1rem;

            padding: 0.8rem 2.5rem;

            background-color: #ffffff; 

            color: #333333;

            text-decoration: none;

            border: 1px solid #333333;

            border-radius: 0;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1px;

            transition: all 0.3s ease;

        }

        a:hover { 

            background-color: #333333;

            color: #ffffff; 

        }

    </style>

</head>

<body>

    <div class='error-container'>

        <div class='logo'>SYS INESCOLARA</div>

        <h1>404</h1>

        <h2>Página no encontrada</h2>

        <p>Lo sentimos, la página que buscas no existe o se ha movido.</p>

        <a href='" . BASE_URL . "'>Volver al Inicio</a>

    </div>

</body>

</html>";
        }

        exit();
    }
}
