<?php
/**
 * Nyitott Múlt – Egyszerű, tiszta URL router
 */

declare(strict_types=1);

namespace NyitottMult;

class Router
{
    private static array $routes = [];

    public static function get(string $path, callable|string $handler): void
    {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post(string $path, callable|string $handler): void
    {
        self::$routes['POST'][$path] = $handler;
    }

    public static function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Nyelv előtag levágása, ha az URL-ben van (pl. /hu/terkep vagy /en/terkep)
        $uriParts = explode('/', trim($uri, '/'));
        if (!empty($uriParts[0]) && in_array($uriParts[0], ['hu', 'en'], true)) {
            array_shift($uriParts);
            $uri = '/' . implode('/', $uriParts);
        }

        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        // Pontos egyezés vizsgálata
        if (isset(self::$routes[$method][$uri])) {
            self::executeHandler(self::$routes[$method][$uri]);
            return;
        }

        // Paraméteres útvonalak vizsgálata (pl. /dokumentumok/{id}, /entitasok/{id})
        foreach (self::$routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\-]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                self::executeHandler($handler, $params);
                return;
            }
        }

        // 404 Nem található
        http_response_code(404);
        include dirname(__DIR__) . '/views/layout/header.php';
        echo '<div class="max-w-4xl mx-auto px-4 py-16 text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">404 - Az oldal nem található</h1>
                <p class="text-gray-600 mb-8">A keresett helytörténeti oldal vagy irat nem érhető el.</p>
                <a href="/" class="btn-primary">Vissza a kezdőlapra</a>
              </div>';
        include dirname(__DIR__) . '/views/layout/footer.php';
    }

    private static function executeHandler(callable|string $handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, [$params]);
        } elseif (is_string($handler)) {
            $viewPath = dirname(__DIR__) . '/views/' . $handler . '.php';
            if (file_exists($viewPath)) {
                extract($params);
                include $viewPath;
            } else {
                http_response_code(500);
                echo "Hiba: A nézet ({$handler}) nem található.";
            }
        }
    }
}
