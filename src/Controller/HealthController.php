<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\JsonResponse;
use OpenApi\Attributes as OA;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Redis;
use Throwable;

final class HealthController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?Redis $redis
    ) {
    }

    #[OA\Get(
        path: '/health',
        operationId: 'healthCheck',
        summary: 'Verifica a saúde da API, conexão com MySQL 8.4 e Redis',
        tags: ['Infraestrutura'],
        responses: [
            new OA\Response(response: 200, description: 'Todos os serviços operacionais'),
            new OA\Response(response: 503, description: 'Algum serviço degradado'),
        ]
    )]
    public function check(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dbOk = false;
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $dbOk = $stmt !== false;
        } catch (Throwable) {
            $dbOk = false;
        }

        $redisOk = false;
        if ($this->redis !== null) {
            try {
                $ping = $this->redis->ping();
                $redisOk = $ping === true || $ping === '+PONG' || $ping === 'PONG';
            } catch (Throwable) {
                $redisOk = false;
            }
        }

        $healthy = $dbOk;
        $status = $healthy ? 200 : 503;

        return JsonResponse::send($response, [
            'status' => $healthy ? 'ok' : 'degraded',
            'service' => 'chezvous-departamentos-api',
            'checks' => [
                'mysql' => $dbOk ? 'up' : 'down',
                'redis' => $redisOk ? 'up' : 'down',
            ],
            'timestamp' => date(DATE_ATOM),
        ], $status);
    }
}

