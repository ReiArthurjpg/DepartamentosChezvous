<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\StandaloneServerRequest;

final class CorsMiddlewareTest extends TestCase
{
    public function testPreflightOptionsReturns204WithCorsHeaders(): void
    {
        $middleware = new CorsMiddleware('*');
        $request = (new StandaloneServerRequest('OPTIONS', '/api/v1/admin/departamentos', [], [], ''))
            ->withHeader('Origin', 'http://localhost:3000');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertStringContainsString('X-API-Key', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }
}

