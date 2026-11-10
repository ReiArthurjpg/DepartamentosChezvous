<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Exception\ApiException;
use App\Middleware\JsonErrorMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\StandaloneServerRequest;

final class JsonErrorMiddlewareTest extends TestCase
{
    public function testCatchesApiExceptionAndFormatsJson(): void
    {
        $middleware = new JsonErrorMiddleware(false);
        $request = new StandaloneServerRequest('POST', '/api/v1/admin/departamentos', [], [], '');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw ApiException::validation(['nome' => 'Obrigatório']);
            }
        };

        $response = $middleware->process($request, $handler);
        $decoded = json_decode((string) $response->getBody(), true);

        self::assertSame(422, $response->getStatusCode());
        self::assertTrue($decoded['error']);
        self::assertSame('VALIDATION_ERROR', $decoded['code']);
        self::assertSame('Obrigatório', $decoded['errors']['nome']);
    }
}

