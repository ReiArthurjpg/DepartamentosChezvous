<?php

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require $vendorAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = __DIR__ . '/../src/' . $relative . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}
require_once __DIR__ . '/../src/Support/StandaloneRuntime.php';

use App\Controller\ColaboradorController;
use App\Controller\DepartamentoController;
use App\Controller\HealthController;
use App\Exception\ApiException;
use App\Middleware\AdminApiKeyMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonErrorMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Repository\ColaboradorRepository;
use App\Repository\DepartamentoRepository;
use App\Service\ColaboradorService;
use App\Service\DepartamentoService;
use App\Support\JsonResponse;
use App\Support\SimpleContainer;
use App\Support\StandaloneApp;
use App\Validation\ColaboradorValidator;
use App\Validation\DepartamentoValidator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Carrega variáveis de ambiente do .env ou .env.prod
(static function (string $rootDir): void {
    $envFiles = [$rootDir . '/.env', $rootDir . '/.env.prod'];
    foreach ($envFiles as $envFile) {
        if (!is_file($envFile) || !is_readable($envFile)) {
            continue;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($name !== '' && !isset($_ENV[$name]) && getenv($name) === false) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
        break;
    }
})(dirname(__DIR__));

date_default_timezone_set((string) ($_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo'));

$container = new SimpleContainer([
    PDO::class => static function (): PDO {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        $host = (string) ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
        $port = (string) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
        $db = (string) ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '');
        $user = (string) ($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: '');
        $pass = (string) ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '');

        if ($db === '' || $user === '') {
            throw new ApiException(
                'Configuração de banco de dados ausente no servidor (.env).',
                500,
                'DB_CONFIG_MISSING'
            );
        }

        $ip = gethostbyname($host);
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $ip, $port, $db);

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);
        return $pdo;
    },

    Redis::class => static function (): ?Redis {
        static $redis = null;
        static $initialized = false;
        if ($initialized) {
            return $redis;
        }
        $initialized = true;
        if (!class_exists(Redis::class)) {
            return null;
        }
        try {
            $client = new Redis();
            $host = (string) ($_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '127.0.0.1');
            $port = (int) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379);
            $ip = gethostbyname($host);
            $connected = @$client->pconnect($ip, $port, 0.5, 'chezvous_pool');
            if (!$connected) {
                return null;
            }
            $redis = $client;
            return $redis;
        } catch (Throwable) {
            return null;
        }
    },

    DepartamentoRepository::class => static fn (ContainerInterface $c): DepartamentoRepository => new DepartamentoRepository(
        $c->get(PDO::class)
    ),

    ColaboradorRepository::class => static fn (ContainerInterface $c): ColaboradorRepository => new ColaboradorRepository(
        $c->get(PDO::class)
    ),

    DepartamentoValidator::class => static fn (): DepartamentoValidator => new DepartamentoValidator(),

    ColaboradorValidator::class => static fn (): ColaboradorValidator => new ColaboradorValidator(),

    DepartamentoService::class => static fn (ContainerInterface $c): DepartamentoService => new DepartamentoService(
        $c->get(DepartamentoRepository::class),
        $c->get(ColaboradorRepository::class),
        $c->get(DepartamentoValidator::class)
    ),

    ColaboradorService::class => static fn (ContainerInterface $c): ColaboradorService => new ColaboradorService(
        $c->get(ColaboradorRepository::class),
        $c->get(DepartamentoRepository::class),
        $c->get(ColaboradorValidator::class)
    ),

    DepartamentoController::class => static fn (ContainerInterface $c): DepartamentoController => new DepartamentoController(
        $c->get(DepartamentoService::class)
    ),

    ColaboradorController::class => static fn (ContainerInterface $c): ColaboradorController => new ColaboradorController(
        $c->get(ColaboradorService::class)
    ),

    HealthController::class => static fn (ContainerInterface $c): HealthController => new HealthController(
        $c->get(PDO::class),
        $c->get(Redis::class)
    ),
]);

$app = new StandaloneApp($container);

// Detecta automaticamente o BasePath para subdiretórios (ex.: /Departamentos na Hostinger)
$requestUriPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

$detectedBasePath = '';
if ($scriptDir !== '' && $scriptDir !== '/') {
    if (str_starts_with($requestUriPath, $scriptDir)) {
        $detectedBasePath = $scriptDir;
    } elseif (str_ends_with($scriptDir, '/public')) {
        $parentDir = substr($scriptDir, 0, -7);
        if ($parentDir !== '' && str_starts_with($requestUriPath, $parentDir)) {
            $detectedBasePath = $parentDir;
        }
    }
}

if ($detectedBasePath === '') {
    $configuredBasePath = '/' . trim((string) ($_ENV['APP_BASE_PATH'] ?? getenv('APP_BASE_PATH') ?: ''), '/');
    if ($configuredBasePath !== '/' && str_starts_with($requestUriPath, $configuredBasePath)) {
        $detectedBasePath = $configuredBasePath;
    }
}

if ($detectedBasePath !== '') {
    $app->setBasePath($detectedBasePath);
}

// Middlewares Globais: Rate Limit, Erros JSON e CORS
$redisInstance = $container->get(Redis::class);
$app->add(new RateLimitMiddleware(
    $redisInstance,
    (int) ($_ENV['RATE_LIMIT_WRITE_PER_MIN'] ?? getenv('RATE_LIMIT_WRITE_PER_MIN') ?: 60),
    (int) ($_ENV['RATE_LIMIT_GENERAL_PER_MIN'] ?? getenv('RATE_LIMIT_GENERAL_PER_MIN') ?: 300)
));

$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN);
$app->add(new JsonErrorMiddleware($debug));
$app->add(new CorsMiddleware(
    (string) ($_ENV['CORS_ALLOWED_ORIGINS'] ?? getenv('CORS_ALLOWED_ORIGINS') ?: '*')
));

// Rota raiz com informações rápidas e links da documentação
$app->get('/', static function (ServerRequestInterface $request, ResponseInterface $response) use ($app): ResponseInterface {
    $base = $app->getBasePath();
    return JsonResponse::send($response, [
        'name' => 'Chez Vous - API de Departamentos & Colaboradores',
        'version' => '1.0.0',
        'docs' => $base . '/docs',
        'openapi' => $base . '/openapi.json',
        'health' => $base . '/health',
        'endpoints' => [
            'departamentos_stats' => 'GET ' . $base . '/api/v1/admin/departamentos/stats',
            'departamentos_list' => 'GET ' . $base . '/api/v1/admin/departamentos',
            'departamentos_create' => 'POST ' . $base . '/api/v1/admin/departamentos',
            'departamentos_detail' => 'GET ' . $base . '/api/v1/admin/departamentos/{id}',
            'departamentos_update' => 'PUT ' . $base . '/api/v1/admin/departamentos/{id}',
            'departamentos_delete' => 'DELETE ' . $base . '/api/v1/admin/departamentos/{id}',
            'colaboradores_all' => 'GET ' . $base . '/api/v1/admin/colaboradores',
            'colaboradores_list_by_dep' => 'GET ' . $base . '/api/v1/admin/departamentos/{id}/colaboradores',
            'colaboradores_create' => 'POST ' . $base . '/api/v1/admin/departamentos/{id}/colaboradores',
            'colaboradores_update' => 'PUT ' . $base . '/api/v1/admin/colaboradores/{colabId}',
            'colaboradores_delete' => 'DELETE ' . $base . '/api/v1/admin/colaboradores/{colabId}',
        ],
    ]);
});

$app->get('/health', [HealthController::class, 'check']);

$app->get('/openapi.json', static function (ServerRequestInterface $request, ResponseInterface $response) use ($app): ResponseInterface {
    $staticFile = __DIR__ . '/../docs/openapi.json';
    $basePath = $app->getBasePath();
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        ? 'https'
        : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8003';
    $currentServerUrl = rtrim($scheme . '://' . $host . $basePath, '/');

    if (is_file($staticFile)) {
        $raw = (string) file_get_contents($staticFile);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $decoded['servers'] = [
                ['url' => $currentServerUrl, 'description' => 'Servidor Atual (' . $host . ')'],
                ['url' => 'http://localhost:8003', 'description' => 'Servidor Docker Local (Porta 8003)'],
            ];
            $response->getBody()->write((string) json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }
        $response->getBody()->write($raw);
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
    return JsonResponse::send($response, ['error' => 'openapi.json not found'], 404);
});

$app->get('/docs', static function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $htmlFile = __DIR__ . '/../docs/index.html';
    $response->getBody()->write((string) file_get_contents($htmlFile));
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});

// Grupo principal v1
$app->group('/api/v1', function ($group): void {
    $adminKey = trim((string) ($_ENV['ADMIN_API_KEY'] ?? getenv('ADMIN_API_KEY') ?: ''));
    $authMiddleware = new AdminApiKeyMiddleware($adminKey);

    $group->group('/departamentos', function ($d): void {
        $d->get('/stats', [DepartamentoController::class, 'stats']);
        $d->get('', [DepartamentoController::class, 'list']);
        $d->post('', [DepartamentoController::class, 'create']);
        $d->get('/{id}', [DepartamentoController::class, 'detail']);
        $d->put('/{id}', [DepartamentoController::class, 'update']);
        $d->patch('/{id}', [DepartamentoController::class, 'update']);
        $d->delete('/{id}', [DepartamentoController::class, 'delete']);
        $d->get('/{id}/colaboradores', [ColaboradorController::class, 'listByDepartamento']);
        $d->post('/{id}/colaboradores', [ColaboradorController::class, 'create']);
    })->add($authMiddleware);

    $group->group('/colaboradores', function ($c): void {
        $c->get('', [ColaboradorController::class, 'listAll']);
        $c->put('/{colabId}', [ColaboradorController::class, 'update']);
        $c->patch('/{colabId}', [ColaboradorController::class, 'update']);
        $c->delete('/{colabId}', [ColaboradorController::class, 'delete']);
    })->add($authMiddleware);

    $group->group('/admin', function ($admin): void {
        $admin->get('/departamentos/stats', [DepartamentoController::class, 'stats']);
        $admin->get('/departamentos', [DepartamentoController::class, 'list']);
        $admin->post('/departamentos', [DepartamentoController::class, 'create']);
        $admin->get('/departamentos/{id}', [DepartamentoController::class, 'detail']);
        $admin->put('/departamentos/{id}', [DepartamentoController::class, 'update']);
        $admin->patch('/departamentos/{id}', [DepartamentoController::class, 'update']);
        $admin->delete('/departamentos/{id}', [DepartamentoController::class, 'delete']);

        $admin->get('/departamentos/{id}/colaboradores', [ColaboradorController::class, 'listByDepartamento']);
        $admin->post('/departamentos/{id}/colaboradores', [ColaboradorController::class, 'create']);

        $admin->get('/colaboradores', [ColaboradorController::class, 'listAll']);
        $admin->put('/colaboradores/{colabId}', [ColaboradorController::class, 'update']);
        $admin->patch('/colaboradores/{colabId}', [ColaboradorController::class, 'update']);
        $admin->delete('/colaboradores/{colabId}', [ColaboradorController::class, 'delete']);
    })->add($authMiddleware);
});

$app->run();

