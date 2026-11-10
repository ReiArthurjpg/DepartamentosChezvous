<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\ApiException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminApiKeyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $expectedApiKey
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Verifica JWT via Authorization: Bearer <token> ou cookie chezvous_session
        $token = $this->extractBearerToken($request);
        if ($token !== null && $this->isValidJwt($token)) {
            return $handler->handle($request);
        }

        // 2. Fallback para X-API-Key de administração
        $provided = trim($request->getHeaderLine('X-API-Key'));
        if ($provided !== '' && $this->expectedApiKey !== '' && hash_equals($this->expectedApiKey, $provided)) {
            return $handler->handle($request);
        }

        throw ApiException::unauthorized(
            'Autenticação necessária. Faça login novamente ou forneça um token JWT / X-API-Key válido.',
            'UNAUTHORIZED'
        );
    }

    private function extractBearerToken(ServerRequestInterface $request): ?string
    {
        $authHeader = trim($request->getHeaderLine('Authorization'));
        if ($authHeader !== '' && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        $cookies = $request->getCookieParams();
        if (!empty($cookies['chezvous_session']) && is_string($cookies['chezvous_session'])) {
            return trim($cookies['chezvous_session']);
        }

        if (!empty($_COOKIE['chezvous_session']) && is_string($_COOKIE['chezvous_session'])) {
            return trim($_COOKIE['chezvous_session']);
        }

        return null;
    }

    private function isValidJwt(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $secret = trim((string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'chezvous-super-secret-jwt-key-2026-prod'));
        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true)
        );

        if (!hash_equals($expectedSig, $signatureEncoded)) {
            return false;
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        if (!is_array($payload) || empty($payload['sub'])) {
            return false;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return false;
        }

        return true;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
