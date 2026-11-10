<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ColaboradorService;
use App\Support\JsonResponse;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ColaboradorController
{
    public function __construct(
        private readonly ColaboradorService $service
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/colaboradores',
        operationId: 'adminListAllColaboradores',
        summary: 'Lista todos os colaboradores cadastrados em todos os departamentos',
        tags: ['Colaboradores'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de colaboradores'),
        ]
    )]
    public function listAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $search = isset($query['search']) ? trim((string) $query['search']) : null;

        return JsonResponse::send($response, $this->service->listAll($search));
    }

    #[OA\Get(
        path: '/api/v1/admin/departamentos/{id}/colaboradores',
        operationId: 'adminListColaboradoresByDepartamento',
        summary: 'Lista as pessoas cadastradas em um departamento específico',
        tags: ['Colaboradores'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de colaboradores do departamento'),
            new OA\Response(response: 404, description: 'Departamento não encontrado'),
        ]
    )]
    public function listByDepartamento(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $depId = (string) ($args['id'] ?? '');
        $query = $request->getQueryParams();
        $search = isset($query['search']) ? trim((string) $query['search']) : null;

        return JsonResponse::send($response, $this->service->listByDepartamento($depId, $search));
    }

    #[OA\Post(
        path: '/api/v1/admin/departamentos/{id}/colaboradores',
        operationId: 'adminCreateColaborador',
        summary: 'Adiciona um novo colaborador ao departamento',
        tags: ['Colaboradores'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Colaborador adicionado com sucesso'),
            new OA\Response(response: 404, description: 'Departamento não encontrado'),
            new OA\Response(response: 409, description: 'E-mail já cadastrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $depId = (string) ($args['id'] ?? '');
        $body = self::extractBody($request);
        $created = $this->service->create($depId, $body);

        return JsonResponse::send($response, [
            'message' => 'Colaborador adicionado com sucesso.',
            'item' => $created,
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/colaboradores/{colabId}',
        operationId: 'adminUpdateColaborador',
        summary: 'Atualiza os dados ou departamento de um colaborador',
        tags: ['Colaboradores'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'colabId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Colaborador atualizado com sucesso'),
            new OA\Response(response: 404, description: 'Colaborador não encontrado'),
            new OA\Response(response: 409, description: 'E-mail já cadastrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $colabId = (string) ($args['colabId'] ?? '');
        $body = self::extractBody($request);
        $updated = $this->service->update($colabId, $body);

        return JsonResponse::send($response, [
            'message' => 'Colaborador atualizado com sucesso.',
            'item' => $updated,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/colaboradores/{colabId}',
        operationId: 'adminDeleteColaborador',
        summary: 'Remove um colaborador do departamento',
        tags: ['Colaboradores'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'colabId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Colaborador removido com sucesso'),
            new OA\Response(response: 404, description: 'Colaborador não encontrado'),
        ]
    )]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $colabId = (string) ($args['colabId'] ?? '');
        $this->service->delete($colabId);

        return JsonResponse::send($response, [
            'message' => 'Colaborador removido com sucesso.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractBody(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }
        $raw = (string) $request->getBody();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

