<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Exception\ApiException;
use App\Middleware\AdminApiKeyMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\StandaloneServerRequest;

final class AdminApiKeyMiddlewareTest extends TestCase
{
    private function createHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response(200);
                $response->getBody()->write((string) json_encode(['status' => 'authorized']));
                return $response;
            }
        };
    }

    public function testAllowsRequestWhenApiKeyMatches(): void
    {
        $expectedKey = 'cv_admin_secret_key_2026_dev';
        $middleware = new AdminApiKeyMiddleware($expectedKey);

        $request = (new StandaloneServerRequest('GET', '/api/v1/admin/departamentos', [], [], ''))
            ->withHeader('X-API-Key', $expectedKey);

        $response = $middleware->process($request, $this->createHandler());

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('authorized', (string) $response->getBody());
    }

    public function testRejectsRequestWhenApiKeyIsMissing(): void
    {
        $middleware = new AdminApiKeyMiddleware('cv_admin_secret_key_2026_dev');
        $request = new StandaloneServerRequest('GET', '/api/v1/admin/departamentos', [], [], '');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(401);

        $middleware->process($request, $this->createHandler());
    }

    public function testRejectsRequestWhenApiKeyIsInvalid(): void
    {
        $middleware = new AdminApiKeyMiddleware('cv_admin_secret_key_2026_dev');
        $request = (new StandaloneServerRequest('GET', '/api/v1/admin/departamentos', [], [], ''))
            ->withHeader('X-API-Key', 'chave_invalida');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(401);

        $middleware->process($request, $this->createHandler());
    }
}

